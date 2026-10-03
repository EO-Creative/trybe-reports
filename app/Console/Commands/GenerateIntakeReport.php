<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Jobs\GenerateIntakeReport as GenerateIntakeReportJob;

#[Signature('trybe:intake-report {--queue : Queue the job instead of running immediately} {--tomorrow : Generate the report for tomorrow\'s orders}')]
#[Description('Generate the intake report')]
class GenerateIntakeReport extends Command
{
	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		if( $this->option('queue') ) {
			GenerateIntakeReportJob::dispatch();

			$this->info('Intake form completion report job dispatched to queue.');

			return self::SUCCESS;
		}

		$timeStart = microtime(true);

		$job = new GenerateIntakeReportJob();
		$filePath = $job->handle();

		$duration = microtime(true) - $timeStart;

		$this->info("Intake form completion report generated at [{$filePath}] in " . number_format($duration, 4) . " seconds.");

		return self::SUCCESS;
	}
}
