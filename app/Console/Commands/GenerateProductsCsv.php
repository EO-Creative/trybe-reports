<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Jobs\GenerateProductsCsv as GenerateProductsCsvJob;

#[Signature('trybe:export-products {--queue : Queue the job instead of running immediately}')]
#[Description('Generate products data CSV export')]
class GenerateProductsCsv extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if( $this->option('queue') ) {
            GenerateProductsCsvJob::dispatch();

            $this->info('Products CSV generation job dispatched to queue.');

            return self::SUCCESS;
        }

        $timeStart = microtime(true);

        $job = new GenerateProductsCsvJob();
        $filePath = $job->handle();

        $duration = microtime(true) - $timeStart;

        $this->info("Products Data CSV generated at [{$filePath}] in " . number_format($duration, 4) . " seconds.");

        return self::SUCCESS;
    }
}
