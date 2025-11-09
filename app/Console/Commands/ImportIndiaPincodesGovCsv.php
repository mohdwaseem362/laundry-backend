<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Country;
use Carbon\Carbon;

class ImportIndiaPincodesGovCsv extends Command
{
    // php artisan import:pincodes:gov-csv --path=imports/pincodes_india.csv --chunk=1000

    protected $signature = 'import:pincodes:gov-csv
        {--path=imports/pincodes_india.csv : Storage path under storage/app/ }
        {--chunk=1000 : number of rows to upsert per transaction}
    ';
    protected $description = 'Import Indian pincodes from the government CSV into pincodes table (storage/app path)';

    public function handle()
    {
        $relPath = $this->option('path') ?: 'imports/pincodes_india.csv';
        $chunkSize = (int) $this->option('chunk') ?: 1000;
        $fullPath = storage_path('app/' . $relPath);

        if (!file_exists($fullPath)) {
            $this->error("CSV file not found at: {$fullPath}");
            return self::FAILURE;
        }

        $country = Country::whereRaw('upper(iso2) = ?', ['IN'])->first();
        if (!$country) {
            $this->error("Country record for ISO2='IN' not found. Run import:countries first.");
            return self::FAILURE;
        }
        $countryId = $country->id;

        $this->info("Starting import from: {$fullPath}");
        $this->info("Chunk size: {$chunkSize}");

        $fh = fopen($fullPath, 'r');
        if (!$fh) {
            $this->error("Failed to open file: {$fullPath}");
            return self::FAILURE;
        }

        // read header row and normalize keys
        $header = fgetcsv($fh);
        if (!is_array($header)) {
            $this->error('Invalid CSV header');
            fclose($fh);
            return self::FAILURE;
        }
        $header = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $rows = [];
        $count = 0;
        $inserted = 0;
        $now = Carbon::now()->toDateTimeString();

        // expected headers (common based on your screenshot)
        // we will tolerant many variations and try to map keys
        while (($data = fgetcsv($fh)) !== false) {
            $count++;
            $assoc = array_combine($header, $data);

            // normalize commonly-used keys
            $pincode = $this->value($assoc, ['pincode', 'pin', 'postalcode', 'postoffice', 'post_office']);
            if ($pincode === null || trim($pincode) === '') {
                // skip rows without pincode
                continue;
            }
            $pincode = trim($pincode);

            $officename = $this->value($assoc, ['officename', 'office_name', 'officename ']);
            $district = $this->value($assoc, ['districtname', 'district', 'district_name']);
            $state = $this->value($assoc, ['statename', 'state', 'state_name']);
            $division = $this->value($assoc, ['divisionname', 'division', 'division_name']);
            $region = $this->value($assoc, ['regionname', 'region', 'region_name']);
            $circle = $this->value($assoc, ['circlename', 'circle', 'circle_name']);
            $officetype = $this->value($assoc, ['officetype', 'office_type', 'type']);
            $deliverystatus = $this->value($assoc, ['deliverystatus', 'delivery', 'deliverystatus ']);
            $lat = $this->value($assoc, ['latitude', 'lat']);
            $lng = $this->value($assoc, ['longitude', 'lon', 'lng']);
            // treat NA or empty as null for lat/lng
            $lat = $this->nullIfNa($lat);
            $lng = $this->nullIfNa($lng);

            // build meta (additional information)
            $meta = [
                'office_name' => $officename ?: null,
                'office_type' => $officetype ?: null,
                'delivery_status' => $deliverystatus ?: null,
                'division' => $division ?: null,
                'region' => $region ?: null,
                'circle' => $circle ?: null,
            ];

            $row = [
                'pincode'      => $pincode,
                'city'         => $district ?: null,
                'state'        => $state ?: null,
                'country_id'   => $countryId,
                'latitude'     => is_numeric($lat) ? (float)$lat : null,
                'longitude'    => is_numeric($lng) ? (float)$lng : null,
                'active'       => true,
                'meta'         => json_encode($meta),
                'created_at'   => $now,
                'updated_at'   => $now,
            ];

            $rows[] = $row;

            // if chunk reached, upsert and reset
            if (count($rows) >= $chunkSize) {
                $this->upsertBatch($rows);
                $inserted += count($rows);
                $this->info("Imported {$inserted} rows so far...");
                $rows = [];
            }
        }

        // final batch
        if (!empty($rows)) {
            $this->upsertBatch($rows);
            $inserted += count($rows);
        }

        fclose($fh);

        $this->info("✅ Completed import. Total scanned rows: {$count}. Upserted: {$inserted}.");
        Log::info('import:pincodes:gov-csv finished', ['file' => $fullPath, 'scanned' => $count, 'upserted' => $inserted]);

        return self::SUCCESS;
    }

    /**
     * Helper to upsert a batch.
     */
    protected function upsertBatch(array $rows)
    {
        if (empty($rows)) return;

        // prefer DB::table upsert for bulk performance
        // unique keys: assume (pincode, country_id) is the uniqueness constraint
        // update these fields when conflict occurs
        $updateColumns = ['city', 'state', 'latitude', 'longitude', 'active', 'meta', 'updated_at'];

        try {
            DB::table('pincodes')->upsert($rows, ['pincode', 'country_id'], $updateColumns);
        } catch (\Throwable $e) {
            // fallback to row-by-row upsert to avoid losing data, and log the error
            Log::error('Bulk upsert failed, falling back to row-by-row', ['err' => $e->getMessage()]);
            foreach ($rows as $r) {
                try {
                    DB::table('pincodes')->updateOrInsert(
                        ['pincode' => $r['pincode'], 'country_id' => $r['country_id']],
                        array_intersect_key($r, array_flip(array_merge($updateColumns, ['created_at', 'updated_at', 'pincode', 'country_id'])))
                    );
                } catch (\Throwable $ex) {
                    Log::error('Row upsert failed', ['pincode' => $r['pincode'], 'err' => $ex->getMessage()]);
                }
            }
        }
    }

    protected function value(array $assoc, array $keys)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $assoc)) {
                return $assoc[$k];
            }
        }
        return null;
    }

    protected function nullIfNa($v)
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        if ($v === '' || strcasecmp($v, 'na') === 0 || strcasecmp($v, 'n/a') === 0) return null;
        return $v;
    }
}
