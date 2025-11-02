<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Country;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Jobs\ImportCountriesJob;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $q = $request->input('q', null);
        $query = Country::query();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('iso2', 'like', "%{$q}%")
                    ->orWhere('iso3', 'like', "%{$q}%");
            });
        }

        $countries = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Countries/Index', [
            'countries' => $countries,
            'filters' => $request->only(['q', 'per_page']),
        ]);
    }

    /**
     * Sync countries using the import:countries artisan command.
     */
    public function sync(Request $request)
    {
        try {
            ImportCountriesJob::dispatch(); // queued job
            return redirect()->back()->with('success', 'Sync started — running in background.');
        } catch (\Throwable $e) {
            \Log::error('Dispatching ImportCountriesJob failed', ['err' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to start sync: ' . $e->getMessage());
        }
    }

    // optional edit/update endpoints (for admin edit)
    public function edit($id)
    {
        $country = Country::findOrFail($id);
        return Inertia::render('Admin/Countries/Edit', ['country' => $country]);
    }

    public function update(Request $request, $id)
    {
        $country = Country::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'iso2' => 'required|string|size:2',
            'iso3' => 'nullable|string|size:3',
            'currency_id' => 'nullable|exists:currencies,id',
            'timezone' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $country->update(array_merge($data, ['active' => (bool) ($data['active'] ?? true)]));

        return redirect()->route('admin.countries.index')->with('success', 'Country updated.');
    }
}
