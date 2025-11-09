<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Country;
use Carbon\Carbon;

class ImportIndiaPincodesGovApi extends Command
{
    protected $signature = 'import:pincodes:gov-api
        {--api-key= : override API key}
        {--url= : override API base URL}
        {--limit= : records per request}
        {--start-offset=0 : start offset}
        {--format=json : response format (json or csv)}
        {--sleep-ms=200 : milliseconds to sleep between requests}
    ';
    protected $description = 'Import Indian pincodes using government data.gov API (paginated resource).';

    public function handle()
    {
        $apiKey = $this->option('api-key') ?: env('PINCODE_GOV_API_KEY');
        $baseUrl = $this->option('url') ?: env('PINCODE_GOV_API_URL');
        $limit = (int) ($this->option('limit') ?: env('PINCODE_GOV_API_LIMIT', 1000));
        $format = $this->option('format') ?: env('PINCODE_GOV_API_FORMAT', 'json');
        $sleepMs = (int) ($this->option('sleep-ms') ?: env('PINCODE_GOV_API_SLEEP_MS', 200));
        $offset = (int) $this->option('start-offset');

        if (! $baseUrl) {
            $this->error('PINCODE_GOV_API_URL not configured (or pass --url).');
            return self::FAILURE;
        }
        if (! $apiKey) {
            $this->warn('No API key provided. You can still try with sample key, but register on data.gov for a full key.');
        }

        $country = Country::whereRaw('upper(iso2) = ?', ['IN'])->first();
        if (! $country) {
            $this->error("Country with iso2='IN' not found. Run import:countries first.");
            return self::FAILURE;
        }
        $countryId = $country->id;

        $this->info("Starting import from {$baseUrl} (format={$format}, limit={$limit}, offset={$offset})");
        $totalFetched = 0;
        $now = Carbon::now()->toDateTimeString();

        while (true) {
            // build query params expected by the API: api-key, format, offset, limit
            $params = [
                'api-key' => $apiKey,
                'format' => $format,
                'offset' => $offset,
                'limit' => $limit,
            ];

            try {
                $res = Http::timeout(60)->get($baseUrl, $params);
            } catch (\Throwable $e) {
                Log::error('import:pincodes:gov-api http error', ['err' => $e->getMessage(), 'offset' => $offset]);
                $this->error("HTTP request failed at offset {$offset}: " . $e->getMessage());
                return self::FAILURE;
            }

            if (! $res->ok()) {
                Log::warning('import:pincodes:gov-api non-ok response', ['status' => $res->status(), 'offset' => $offset, 'body' => substr($res->body(), 0, 200)]);
                $this->error("Remote request returned status {$res->status()} at offset {$offset}");
                return self::FAILURE;
            }

            $rows = [];

            if ($format === 'csv' || str_contains(strtolower($res->header('Content-Type') ?? ''), 'text/csv')) {
                // parse CSV body
                $body = $res->body();
                // temporary file stream
                $tmp = tmpfile();
                fwrite($tmp, $body);
                fseek($tmp, 0);
                $header = null;
                while (($data = fgetcsv($tmp)) !== false) {
                    if (! $header) {
                        $header = array_map(fn($h) => strtolower(trim($h)), $data);
                        continue;
                    }
                    $assoc = array_combine($header, $data);
                    $rows[] = $this->mapGovRow($assoc, $countryId);
                }
                fclose($tmp);
            } else {
                // assume JSON array
                $payload = $res->json();
                if (! is_array($payload) || empty($payload)) {
                    $this->info("No more rows returned at offset {$offset}. Breaking loop.");
                    break;
                }
                foreach ($payload as $item) {
                    // item keys likely match CSV header names; normalize by lowercasing keys
                    $normalized = [];
                    foreach ($item as $k => $v) $normalized[strtolower($k)] = $v;
                    $rows[] = $this->mapGovRow($normalized, $countryId);
                }
            }

            $fetched = count(array_filter($rows));
            $this->info("Fetched {$fetched} rows at offset {$offset}.");

            if ($fetched === 0) {
                // no rows -> done
                break;
            }

            // upsert batch in chunks to avoid memory spikes
            $batchSize = 1000;
            $batches = array_chunk($rows, $batchSize);
            foreach ($batches as $batch) {
                $this->upsertBatch($batch);
            }

            $totalFetched += $fetched;
            $this->info("Total imported so far: {$totalFetched}");

            // stop if fewer rows returned than requested (end)
            if ($fetched < $limit) {
                $this->info("Returned rows {$fetched} < limit {$limit} — assuming end of dataset.");
                break;
            }

            // increment offset and continue
            $offset += $limit;

            // polite pause
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info("✅ Complete. Total rows imported/updated: {$totalFetched}");
        Log::info('import:pincodes:gov-api finished', ['total' => $totalFetched]);

        return self::SUCCESS;
    }

    /**
     * Map a normalized gov row (assoc with lowercase keys) to our DB shape.
     */
    protected function mapGovRow(array $assoc, int $countryId): ?array
    {
        // common keys from the data.gov resource (based on your screenshot / CSV)
        // e.g. circlename, regionname, divisionname, officename, pincode, officetype, deliverystatus, districtname, statename, latitude, longitude
        $pincode = $assoc['pincode'] ?? $assoc['pin'] ?? null;
        if (! $pincode || trim($pincode) === '') return null;

        $p = trim($pincode);
        $district = $assoc['districtname'] ?? $assoc['district'] ?? null;
        $state = $assoc['statename'] ?? $assoc['state'] ?? null;
        $officename = $assoc['officename'] ?? null;
        $division = $assoc['divisionname'] ?? null;
        $region = $assoc['regionname'] ?? null;
        $circle = $assoc['circlename'] ?? null;
        $officetype = $assoc['officetype'] ?? null;
        $deliverystatus = $assoc['deliverystatus'] ?? $assoc['delivery'] ?? null;
        $lat = $assoc['latitude'] ?? ($assoc['lat'] ?? null);
        $lng = $assoc['longitude'] ?? ($assoc['lng'] ?? ($assoc['lon'] ?? null));
        // normalize NA -> null
        $lat = $this->nullIfNa($lat);
        $lng = $this->nullIfNa($lng);

        $meta = [
            'office_name' => $officename ?: null,
            'office_type' => $officetype ?: null,
            'delivery_status' => $deliverystatus ?: null,
            'division' => $division ?: null,
            'region' => $region ?: null,
            'circle' => $circle ?: null,
        ];

        return [
            'pincode' => $p,
            'city' => $district ?: null,
            'state' => $state ?: null,
            'country_id' => $countryId,
            'latitude' => is_numeric($lat) ? (float)$lat : null,
            'longitude' => is_numeric($lng) ? (float)$lng : null,
            'active' => true,
            'meta' => json_encode($meta),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function nullIfNa($v)
    {
        if ($v === null) return null;
        $v = trim((string) $v);
        if ($v === '' || strcasecmp($v, 'na') === 0 || strcasecmp($v, 'n/a') === 0) return null;
        return $v;
    }

    /**
     * Bulk upsert batch using DB::table->upsert for performance.
     */
    protected function upsertBatch(array $rows)
    {
        if (empty($rows)) return;
        $updateColumns = ['city','state','latitude','longitude','active','meta','updated_at'];
        try {
            DB::table('pincodes')->upsert($rows, ['pincode','country_id'], $updateColumns);
        } catch (\Throwable $e) {
            Log::error('import:pincodes:gov-api bulk upsert failed', ['err'=>$e->getMessage()]);
            // fallback: row-by-row updateOrInsert
            foreach ($rows as $r) {
                try {
                    DB::table('pincodes')->updateOrInsert(
                        ['pincode'=>$r['pincode'],'country_id'=>$r['country_id']],
                        array_intersect_key($r, array_flip(array_merge($updateColumns, ['created_at','updated_at'])))
                    );
                } catch (\Throwable $ex) {
                    Log::error('import:pincodes:gov-api upsert row failed', ['pincode'=>$r['pincode'],'err'=>$ex->getMessage()]);
                }
            }
        }
    }
}
