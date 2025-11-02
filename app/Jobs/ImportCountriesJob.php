<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ImportCountriesJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 1200; // 20 minutes (adjust if needed)

    public function __construct() {}

    public function handle()
    {
        try {
            $exit = Artisan::call('import:countries', ['--force' => true]);
            $output = Artisan::output();
            Log::info('ImportCountriesJob finished', ['exit'=>$exit, 'output' => substr($output,0,2000)]);
        } catch (\Throwable $e) {
            Log::error('ImportCountriesJob failed', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}
