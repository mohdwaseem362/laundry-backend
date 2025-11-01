<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use App\Models\Order;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends BaseController
{
    public function __construct()
    {
        // requires authenticated user and admin role
        // $this->middleware(['auth', 'role:Admin']);
    }

    // List orders (simple pagination + filters later)
    public function index(Request $request)
    {
        // base query with relations
        $query = Order::with(['user', 'agent'])->orderBy('created_at', 'desc');

        // filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                // match order number, id, or user fields
                $q->where('order_number', 'LIKE', "%{$term}%")
                    ->orWhere('id', $term)
                    ->orWhereHas('user', function ($uq) use ($term) {
                        $uq->where('name', 'LIKE', "%{$term}%")
                            ->orWhere('email', 'LIKE', "%{$term}%");
                    });
            });
        }

        // optional: date range filter (created_at)
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = (int) $request->input('per_page', 15);
        $orders = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            // useful to surface current filters on front-end
            'filters' => $request->only(['q', 'status', 'from', 'to', 'per_page']),
        ]);
    }


    public function create()
    {
        // return customers list only (no services)
        $customers = User::role('Customer')->select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Orders/Create', [
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.service_name' => 'required|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:requested,processing,completed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $data['user_id'],
                'order_number' => 'ORD' . time() . rand(100, 999),
                'status' => $data['status'] ?? 'requested',
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            foreach ($data['items'] as $it) {
                $qty = (int) $it['quantity'];
                $unit = (float) $it['unit_price'];
                $sub = $qty * $unit;

                $order->items()->create([
                    'service_code' => $it['service_code'] ?? null, // optional free text or SKU
                    'service_name' => $it['service_name'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'subtotal' => $sub,
                    'meta' => $it['meta'] ?? null,
                ]);

                $subtotal += $sub;
            }

            // compute tax / discount if you need — basic example (0% tax)
            $tax = 0;
            $total = $subtotal + $tax;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            DB::commit();

            // redirect to show page (your booted() will create conversation)
            return redirect()->route('admin.orders.show', $order->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order store failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to create order.')->withInput();
        }
    }

    // Show single order + conversation + items
    public function show($id)
    {
        $order = Order::with(['items', 'conversation.messages.user', 'user', 'agent'])->findOrFail($id);

        // normalize messages for frontend (optional: transform as needed)
        $messages = $order->conversation
            ? $order->conversation->messages()->with('user')->latest()->get()->map(function ($m) {
                $attachment = null;
                if ($m->attachment) {
                    // attachment stored as JSON/array in DB (see model cast note below)
                    $att = is_array($m->attachment) ? $m->attachment : json_decode($m->attachment, true);
                    if ($att) {
                        $attachment = [
                            'url' => $att['url'] ?? (isset($att['path']) ? Storage::url($att['path']) : null),
                            'filename' => $att['filename'] ?? null,
                            'mime' => $att['mime'] ?? null,
                            'size' => $att['size'] ?? null,
                        ];
                    }
                }

                return [
                    'id' => $m->id,
                    'message' => $m->body ?? $m->message ?? null,
                    'created_at' => optional($m->created_at)->toISOString(),
                    'from' => $m->from ?? ($m->user->name ?? 'System'),
                    'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
                    'attachment' => $attachment,
                ];
            })
            : collect();

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'messages' => $messages,
        ]);
    }

    // Update order status or assign agent
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'nullable|in:requested,accepted,picked,washing,ready,delivered,cancelled',
            'agent_id' => 'nullable|exists:users,id',
        ]);

        $order->fill($request->only(['status', 'agent_id']));
        $order->save();

        // prefer redirect for non-AJAX flows
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'ok', 'order' => $order], Response::HTTP_OK);
        }

        return redirect()->back();
    }

    // Post admin reply in conversation (supports attachment)
    public function message(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $conv = $order->conversation;

        if (! $conv) {
            $conv = $order->conversation()->create(['channel' => 'web']);
        }

        // accept both 'message' (client) and 'body' (older)
        $textKey = $request->has('message') ? 'message' : 'body';

        $rules = [
            $textKey => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:png,jpg,jpeg,gif,pdf,doc,docx,xls,xlsx,txt',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $attachmentData = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');

            // disk name from config (e.g. 's3' pointing to MinIO locally)
            $diskName = config('filesystems.default');

            // create a deterministic path — include order id to organize files
            $dir = "order_attachments/{$order->id}";

            // generate a safe filename to avoid collisions
            $ext = $file->getClientOriginalExtension();
            $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = $safeName . '-' . Str::random(8) . '.' . $ext;

            try {
                // store file with public visibility (dev). For production use temporaryUrl or proxy.
                $path = Storage::disk($diskName)->putFileAs($dir, $file, $filename, ['visibility' => 'public']);

                // build url (public)
                $url = Storage::disk($diskName)->url($path);

                $attachmentData = [
                    'path' => $path,
                    'url' => $url,
                    'filename' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            } catch (\Throwable $e) {
                Log::error('Attachment upload failed', ['error' => $e->getMessage(), 'order_id' => $order->id]);
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['error' => 'Failed to upload attachment'], 500);
                }
                return redirect()->back()->with('error', 'Failed to upload attachment.')->withInput();
            }
        }


        $msg = $conv->messages()->create([
            'user_id' => auth()->id(),
            'from' => auth()->user()->name ?? 'Admin',
            'body' => $request->input($textKey),
            'direction' => 'out',
            'attachment' => $attachmentData ? json_encode($attachmentData) : null,
        ]);

        // prepare payload matching the front-end expectations
        $payload = [
            'id' => $msg->id,
            'message' => $msg->body,
            'created_at' => $msg->created_at->toISOString(),
            'from' => $msg->from,
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name ?? 'Admin',
            ],
            'attachment' => $attachmentData ? [
                'url' => $attachmentData['url'],
                'filename' => $attachmentData['filename'],
                'mime' => $attachmentData['mime'],
                'size' => $attachmentData['size'],
            ] : null,
        ];

        // optional: dispatch event to broadcast the new message for real-time UIs
        // event(new \App\Events\OrderMessageCreated($order, $payload));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $payload], Response::HTTP_CREATED);
        }

        return redirect()->back();
    }
}
