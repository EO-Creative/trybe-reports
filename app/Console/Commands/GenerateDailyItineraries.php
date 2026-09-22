<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Jobs\GenerateDailyItineraries as GenerateDailyItinerariesJob;

#[Signature('trybe:itineraries {--queue : Queue the job instead of running immediately}')]
#[Description('Generate daily guest itinerary PDF')]
class GenerateDailyItineraries extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if( $this->option('queue') ) {
            GenerateDailyItinerariesJob::dispatch();

            $this->info('Products CSV generation job dispatched to queue.');

            return self::SUCCESS;
        }

        $timeStart = microtime(true);

        $job = new GenerateDailyItinerariesJob();
        $filePath = $job->handle();

        $duration = microtime(true) - $timeStart;

        $this->info("Products Data CSV generated at [{$filePath}] in " . number_format($duration, 4) . " seconds.");

        return self::SUCCESS;
    }
}
