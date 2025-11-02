<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Country;
use App\Models\Currency;

class ImportCountries extends Command
{
    protected $signature = 'import:countries {--force : Overwrite existing data}';
    protected $description = 'Fetch all countries from REST Countries API and update countries + currencies tables';

    protected $agent = 'laundry-backend-import/1.0 (+https://your.domain)';

    public function handle()
    {
        $this->info('Fetching countries from REST Countries API (v3.1)...');

        // Fields needed for both V3.1 and V2 responses:
        // name, cca2/alpha2Code, cca3/alpha3Code, currencies, timezones, flags/flag, region, subregion, languages/locale
        $fields = 'name,cca2,cca3,currencies,timezones,flags,region,subregion,languages,alpha2Code,alpha3Code,flag';

        // prefer v3.1, fallback to v2 if it fails
        $endpoints = [
            // REST Countries v3.1 (modern)
            'https://restcountries.com/v3.1/all?fields=name,cca2,cca3,currencies,timezones,flags,latlng,region,subregion,languages',
            // fallback v2 (legacy)
            'https://restcountries.com/v2/all?fields=name,alpha2Code,alpha3Code,currencies,timezones,flag,latlng,region,subregion,languages',
        ];


        $payload = null;
        $lastResponseInfo = null;

        foreach ($endpoints as $idx => $url) {
            try {
                // add Accept header + user agent; dev tip: add verify => false temporarily if you still have SSL issues
                $res = Http::withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => $this->agent,
                ])->timeout(30)->get($url);

                $lastResponseInfo = [
                    'url' => $url,
                    'status' => $res->status(),
                    'body_snippet' => substr($res->body(), 0, 200)
                ];

                if ($res->ok() && $res->header('Content-Type') && str_contains($res->header('Content-Type'), 'application/json')) {
                    $payload = $res->json();
                    $this->info("API OK at: {$url} (status {$res->status()})");
                    break;
                }

                $this->warn("API request to {$url} returned status {$res->status()}.");
                // write response body into log for debugging
                Log::warning('import:countries unexpected response', ['url' => $url, 'status' => $res->status(), 'body' => $res->body()]);
            } catch (\Throwable $e) {
                Log::error('import:countries HTTP error', ['url' => $url, 'err' => $e->getMessage()]);
                $this->warn("HTTP request to {$url} failed: {$e->getMessage()}");
            }
        }

        if (! $payload) {
            $this->error('All endpoints failed. Check logs for API response body — last attempt: ' . json_encode($lastResponseInfo));
            return self::FAILURE;
        }

        // Normalise payload shapes between v3 and v2:
        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($payload as $item) {
                // try both shapes
                $iso2 = $item['cca2'] ?? ($item['alpha2Code'] ?? null);
                $iso3 = $item['cca3'] ?? ($item['alpha3Code'] ?? null);
                $name  = $item['name']['common'] ?? ($item['name'] ?? null);

                // v2 name may just be string in $item['name']
                if (!$name && isset($item['name']) && is_string($item['name'])) {
                    $name = $item['name'];
                }

                // fallback for common name in v3 if it uses 'name' key and not nested 'common'
                if (!$name && isset($item['name']) && isset($item['translations']['eng']['common'])) {
                    $name = $item['translations']['eng']['common'];
                }

                if (! $iso2 || ! $iso3 || ! $name) {
                    // skip rows that lack core data
                    continue;
                }

                // currency resolution (v3 returns assoc, v2 returns array)
                $currencyId = null;
                if (!empty($item['currencies']) && is_array($item['currencies'])) {
                    // v3: currencies is assoc 'INR' => {...}
                    $firstKey = array_key_first($item['currencies']);
                    if ($firstKey && is_string($firstKey)) {
                        $currData = $item['currencies'][$firstKey] ?? [];
                        $currency = Currency::updateOrCreate(
                            ['code' => strtoupper($firstKey)],
                            [
                                'name' => $currData['name'] ?? ($currData['fullName'] ?? strtoupper($firstKey)),
                                'symbol' => $currData['symbol'] ?? null,
                                'active' => true,
                                'meta' => json_encode($currData), // Ensure meta is JSON encoded
                            ]
                        );
                        $currencyId = $currency->id;
                    } else {
                        // v2 shape: currencies is numeric array of objects with 'code'
                        $first = $item['currencies'][0] ?? null;
                        if ($first && isset($first['code'])) {
                            $code = strtoupper($first['code']);
                            $currency = Currency::updateOrCreate(
                                ['code' => $code],
                                [
                                    'name' => $first['name'] ?? $code,
                                    'symbol' => $first['symbol'] ?? null,
                                    'active' => true,
                                    'meta' => json_encode($first), // Ensure meta is JSON encoded
                                ]
                            );
                            $currencyId = $currency->id;
                        }
                    }
                }

                $timezone = is_array($item['timezones']) ? ($item['timezones'][0] ?? null) : null;
                $flag     = $item['flags']['png'] ?? $item['flags']['svg'] ?? ($item['flag'] ?? null);

                // Handle v3 languages structure which is associative array
                $locale = null;
                if (isset($item['languages'])) {
                    $locale = json_encode($item['languages']);
                } elseif (isset($item['locale'])) {
                    // For v2 if needed, though 'languages' is better
                    $locale = is_array($item['locale']) ? json_encode($item['locale']) : $item['locale'];
                }


                Country::updateOrCreate(
                    ['iso2' => strtoupper($iso2)],
                    [
                        'name' => $name,
                        'iso3' => strtoupper($iso3),
                        'currency_id' => $currencyId,
                        'timezone' => $timezone,
                        'locale' => $locale,
                        'meta' => json_encode([
                            'flag' => $flag,
                            'region' => $item['region'] ?? null,
                            'subregion' => $item['subregion'] ?? null,
                        ]),
                        'active' => true,
                    ]
                );

                $count++;
            }

            DB::commit();
            $this->info("✅ Imported/updated {$count} countries successfully.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
            Log::error('import:countries failed during DB work', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return self::FAILURE;
        }
    }
}
