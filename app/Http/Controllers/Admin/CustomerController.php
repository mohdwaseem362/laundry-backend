<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q', null);
        $perPage = (int) $request->input('per_page', 15);
        $showDeleted = $request->boolean('deleted', false);

        $query = User::query()
            ->withCount(['orders']) // optional: remove if you don't have orders relation
            ->with(['primaryPhone', 'primaryAddress']);

        // filter only customers (spatie role)
        $query->role('Customer');

        // include trashed if asked
        if ($showDeleted) {
            $query = $query->onlyTrashed();
        }

        if ($q) {
            $term = trim($q);
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%")
                    // fallback single phone search - searches phones table
                    ->orWhereHas('phones', function ($p) use ($term) {
                        $p->where('phone', 'LIKE', "%{$term}%");
                    })
                    ->orWhere('id', $term);
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['q','per_page','deleted']),
        ]);
    }

    public function create()
    {
        // You may want to pass countries/pincodes if you want address autocomplete on create
        return Inertia::render('Admin/Customers/Create', [
            'countries' => \App\Models\Country::select('id','name','iso2','currency_id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',

            // phones array
            'phones' => 'nullable|array',
            'phones.*.phone' => 'required_with:phones|string|max:50',
            'phones.*.type' => 'nullable|string|max:30',
            'phones.*.is_primary' => 'nullable|boolean',

            // addresses array
            'addresses' => 'nullable|array',
            'addresses.*.line1' => 'required_with:addresses|string|max:255',
            'addresses.*.line2' => 'nullable|string|max:255',
            'addresses.*.label' => 'nullable|string|max:100',
            'addresses.*.city' => 'nullable|string|max:100',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.pincode_id' => 'nullable|exists:pincodes,id',
            'addresses.*.country_id' => 'nullable|exists:countries,id',
            'addresses.*.lat' => 'nullable|numeric',
            'addresses.*.lng' => 'nullable|numeric',
            'addresses.*.is_primary' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // assign role Customer (ensure role exists)
            $user->assignRole('Customer');

            // create phones
            if (!empty($data['phones']) && is_array($data['phones'])) {
                foreach ($data['phones'] as $p) {
                    $user->phones()->create([
                        'phone' => $p['phone'],
                        'type' => $p['type'] ?? null,
                        'is_primary' => !empty($p['is_primary']),
                        'meta' => $p['meta'] ?? null,
                    ]);
                }
                $this->ensureSinglePrimaryPhone($user);
            }

            // create addresses
            if (!empty($data['addresses']) && is_array($data['addresses'])) {
                foreach ($data['addresses'] as $a) {
                    $user->addresses()->create([
                        'label' => $a['label'] ?? null,
                        'line1' => $a['line1'] ?? null,
                        'line2' => $a['line2'] ?? null,
                        'city' => $a['city'] ?? null,
                        'state' => $a['state'] ?? null,
                        'pincode_id' => $a['pincode_id'] ?? null,
                        'country_id' => $a['country_id'] ?? null,
                        'lat' => $a['lat'] ?? null,
                        'lng' => $a['lng'] ?? null,
                        'is_primary' => !empty($a['is_primary']),
                        'meta' => $a['meta'] ?? null,
                    ]);
                }
                $this->ensureSinglePrimaryAddress($user);
            }

            DB::commit();

            return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Customer store failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => 'Failed to create customer.']);
        }
    }

    public function edit($id)
    {
        $user = User::withTrashed()->with(['phones','addresses.pincode','addresses.country'])->findOrFail($id);

        return Inertia::render('Admin/Customers/Edit', [
            'customer' => $user,
            // optionally pass countries/pincodes for selects
            'countries' => \App\Models\Country::select('id','name','iso2')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',

            // phones (id optional: if id provided update; else create)
            'phones' => 'nullable|array',
            'phones.*.id' => 'nullable|exists:user_phones,id',
            'phones.*.phone' => 'required_with:phones|string|max:50',
            'phones.*.type' => 'nullable|string|max:30',
            'phones.*.is_primary' => 'nullable|boolean',
            'phones_delete' => 'nullable|array',
            'phones_delete.*' => 'nullable|integer|exists:user_phones,id',

            // addresses
            'addresses' => 'nullable|array',
            'addresses.*.id' => 'nullable|exists:user_addresses,id',
            'addresses.*.line1' => 'required_with:addresses|string|max:255',
            'addresses.*.line2' => 'nullable|string|max:255',
            'addresses.*.label' => 'nullable|string|max:100',
            'addresses.*.city' => 'nullable|string|max:100',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.pincode_id' => 'nullable|exists:pincodes,id',
            'addresses.*.country_id' => 'nullable|exists:countries,id',
            'addresses.*.lat' => 'nullable|numeric',
            'addresses.*.lng' => 'nullable|numeric',
            'addresses.*.is_primary' => 'nullable|boolean',
            'addresses_delete' => 'nullable|array',
            'addresses_delete.*' => 'nullable|integer|exists:user_addresses,id',
        ];

        $data = $request->validate($rules);

        DB::beginTransaction();
        try {
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            // handle phones: update/create
            if (!empty($data['phones']) && is_array($data['phones'])) {
                foreach ($data['phones'] as $p) {
                    if (!empty($p['id'])) {
                        $phone = $user->phones()->where('id', $p['id'])->first();
                        if ($phone) {
                            $phone->update([
                                'phone' => $p['phone'],
                                'type' => $p['type'] ?? null,
                                'is_primary' => !empty($p['is_primary']),
                                'meta' => $p['meta'] ?? null,
                            ]);
                        }
                    } else {
                        $user->phones()->create([
                            'phone' => $p['phone'],
                            'type' => $p['type'] ?? null,
                            'is_primary' => !empty($p['is_primary']),
                            'meta' => $p['meta'] ?? null,
                        ]);
                    }
                }
            }

            // delete phones if requested (soft delete)
            if (!empty($data['phones_delete']) && is_array($data['phones_delete'])) {
                $user->phones()->whereIn('id', $data['phones_delete'])->delete();
            }

            // ensure only one primary phone
            $this->ensureSinglePrimaryPhone($user);

            // handle addresses: update/create
            if (!empty($data['addresses']) && is_array($data['addresses'])) {
                foreach ($data['addresses'] as $a) {
                    $payload = [
                        'label' => $a['label'] ?? null,
                        'line1' => $a['line1'] ?? null,
                        'line2' => $a['line2'] ?? null,
                        'city' => $a['city'] ?? null,
                        'state' => $a['state'] ?? null,
                        'pincode_id' => $a['pincode_id'] ?? null,
                        'country_id' => $a['country_id'] ?? null,
                        'lat' => $a['lat'] ?? null,
                        'lng' => $a['lng'] ?? null,
                        'is_primary' => !empty($a['is_primary']),
                        'meta' => $a['meta'] ?? null,
                    ];

                    if (!empty($a['id'])) {
                        $addr = $user->addresses()->where('id', $a['id'])->first();
                        if ($addr) {
                            $addr->update($payload);
                        }
                    } else {
                        $user->addresses()->create($payload);
                    }
                }
            }

            // delete addresses if requested (soft delete)
            if (!empty($data['addresses_delete']) && is_array($data['addresses_delete'])) {
                $user->addresses()->whereIn('id', $data['addresses_delete'])->delete();
            }

            // ensure only one primary address
            $this->ensureSinglePrimaryAddress($user);

            DB::commit();

            return redirect()->route('admin.customers.index')->with('success', 'Customer updated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Customer update failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => 'Failed to update customer.']);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete(); // soft delete
        return back()->with('success', 'Customer deleted (soft).');
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        if ($user->trashed()) {
            $user->restore();
            return back()->with('success', 'Customer restored.');
        }
        return back()->with('info', 'Customer was not deleted.');
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        // optional: also force delete phones & addresses
        $user->phones()->withTrashed()->forceDelete();
        $user->addresses()->withTrashed()->forceDelete();

        $user->forceDelete();
        return back()->with('success', 'Customer permanently deleted.');
    }

    /* ----------------------
     |  Helper methods
     | ---------------------*/

    protected function ensureSinglePrimaryPhone(User $user): void
    {
        $primary = $user->phones()->where('is_primary', true)->orderByDesc('updated_at')->first();
        if ($primary) {
            $user->phones()->where('id', '!=', $primary->id)->update(['is_primary' => false]);
        }
    }

    protected function ensureSinglePrimaryAddress(User $user): void
    {
        $primary = $user->addresses()->where('is_primary', true)->orderByDesc('updated_at')->first();
        if ($primary) {
            $user->addresses()->where('id', '!=', $primary->id)->update(['is_primary' => false]);
        }
    }
}
