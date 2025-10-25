<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use App\Models\Order;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
        $q = Order::with(['user', 'agent'])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $orders = $q->paginate(15)->withQueryString();
        // dd($orders);
        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
        ]);
    }


    // Show single order + conversation + items
    public function show($id)
    {
        $order = Order::with(['items', 'conversation.messages.user', 'user', 'agent'])->findOrFail($id);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order
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

        return redirect()->back();
    }

    // Post admin reply in conversation
    public function message(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $conv = $order->conversation;

        if (! $conv) {
            $conv = $order->conversation()->create(['channel' => 'web']);
        }

        $request->validate([
            'body' => 'required|string',
        ]);

        $msg = $conv->messages()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
            'direction' => 'out',
        ]);

        // optional: dispatch job to deliver message to WhatsApp / notifications
        return redirect()->back();
    }
}
