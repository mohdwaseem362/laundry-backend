<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Pincode;
use App\Models\Country;
use App\Models\Zone;

class PincodeController extends Controller
{
    /**
     * Display a paginated list of pincodes with optional search.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $q = $request->input('q', null);

        $query = Pincode::with('country');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('pincode', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('state', 'like', "%{$q}%");
            });
        }

        $pincodes = $query->orderBy('pincode')->paginate($perPage)->withQueryString();

        // provide auxiliary lists for create/assign modals (small payload)
        $countries = Country::orderBy('name')->get(['id', 'name', 'iso2']);
        $zones = Zone::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Pincodes/Index', [
            'pincodes' => $pincodes,
            'countries' => $countries,
            'zones' => $zones,
            'filters' => $request->only(['q', 'per_page']),
        ]);
    }

    /**
     * Show the create page (optional — your Index page already has quick-create modal).
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get(['id', 'name', 'iso2']);
        $zones = Zone::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Pincodes/Create', [
            'countries' => $countries,
            'zones' => $zones,
        ]);
    }

    /**
     * Store a new pincode.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'pincode' => 'required|string|max:64|unique:pincodes,pincode',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'active' => 'nullable|boolean',
        ]);

        // normalize boolean
        $data['active'] = (bool) ($data['active'] ?? true);

        Pincode::create($data);

        return redirect()->route('admin.pincodes.index')->with('success', 'Pincode created.');
    }

    /**
     * Edit page for a pincode.
     */
    public function edit($id)
    {
        $pincode = Pincode::with('country', 'zones')->findOrFail($id);
        $countries = Country::orderBy('name')->get(['id', 'name', 'iso2']);
        $zones = Zone::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Pincodes/Edit', [
            'pincode' => $pincode,
            'countries' => $countries,
            'zones' => $zones,
        ]);
    }

    /**
     * Update an existing pincode.
     */
    public function update(Request $request, $id)
    {
        $pincode = Pincode::findOrFail($id);

        $data = $request->validate([
            'pincode' => "required|string|max:64|unique:pincodes,pincode,{$pincode->id}",
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'active' => 'nullable|boolean',
        ]);

        $data['active'] = (bool) ($data['active'] ?? $pincode->active);

        $pincode->update($data);

        return redirect()->route('admin.pincodes.index')->with('success', 'Pincode updated.');
    }

    /**
     * Soft-delete a pincode.
     */
    public function destroy($id)
    {
        $pincode = Pincode::findOrFail($id);
        $pincode->delete();

        return redirect()->route('admin.pincodes.index')->with('success', 'Pincode deleted.');
    }
}
