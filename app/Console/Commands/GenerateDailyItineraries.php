<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Jobs\GenerateDailyItineraries as GenerateDailyItinerariesJob;

#[Signature('trybe:itineraries {--queue : Queue the job instead of running immediately} {--tomorrow : Generate itineraries for guests due to arrive tomorrow}')]
#[Description('Generate guest itineraries PDF')]
class GenerateDailyItineraries extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if( $this->option('queue') ) {
            GenerateDailyItinerariesJob::dispatch();

            $this->info('Daily Itineraries PDF generation job dispatched to queue.');

            return self::SUCCESS;
        }

        $timeStart = microtime(true);

        $job = new GenerateDailyItinerariesJob(tomorrow: !!$this->option('tomorrow'));
        $filePath = $job->handle();

        $duration = microtime(true) - $timeStart;

        $this->info("Daily Itineraries PDF generated at [{$filePath}] in " . number_format($duration, 4) . " seconds.");

        return self::SUCCESS;
    }
}
