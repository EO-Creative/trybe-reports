<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Jobs\GenerateValidationReport as GenerateValidationReportJob;

#[Signature('trybe:validation-report {--queue : Queue the job instead of running immediately} {--tomorrow : Generate the report for tomorrow\'s orders}')]
#[Description('Generate the order validation report')]
class GenerateValidationReport extends Command
{
	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		if( $this->option('queue') ) {
			GenerateValidationReportJob::dispatch();

			$this->info('Order validation report job dispatched to queue.');

			return self::SUCCESS;
		}

		$timeStart = microtime(true);

		$job = new GenerateValidationReportJob();
		$filePath = $job->handle();

		$duration = microtime(true) - $timeStart;

		$this->info("Order validation report generated at [{$filePath}] in " . number_format($duration, 4) . " seconds.");

		return self::SUCCESS;
	}
}