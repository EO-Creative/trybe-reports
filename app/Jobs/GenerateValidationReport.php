<?php

namespace App\Jobs;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use Rap2hpoutre\FastExcel\FastExcel;

use App\Services\TrybeService;

class GenerateValidationReport implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected ?TrybeService $trybe;

	public ?string $outputPath;

	public ?string $today;

	public function __construct(?string $outputPath = null, bool $tomorrow = false)
	{
		$this->trybe = new TrybeService();
		$this->outputPath = $outputPath;
		$this->today = $tomorrow ? Carbon::tomorrow()->format( 'Y-m-d' ) : Carbon::today()->format( 'Y-m-d' );
	}

	/**
	 * Execute the job.
	 */
	public function handle(): string
	{
		$today = $this->today;
		
		$query = [
			'page' => 1,
			'item_date_from' => $today,
			'item_date_to' => $today,
		];

		$orderData = [];
		$todaysOrders = $this->trybe->getOrders($query);

		// dd($todaysOrders);

		$orderData = array_merge( $orderData, $todaysOrders['data'] );
		$lastPage = $todaysOrders['meta']['last_page'] ?? 1;

		if( $lastPage > 1 ) {
			for( $p = 2; $p <= $lastPage; $p++ ) {
				$query['page'] = $p;
				$pageData = $this->trybe->getOrders($query);

				if( !empty( $pageData['data'] ) ) {
					$orderData = array_merge($orderData, $pageData['data'] );
				}
			}
		}

		$validationReports = collect([]);
		foreach( $orderData as $order ) {
			$validationReports->push([
				'Order Reference' => $order[ 'order_ref' ],
				'Created At' => Carbon::parse( $order[ 'created_at' ] )->format( 'jS F Y H:i' ),
				'Updated At' => Carbon::parse( $order[ 'updated_at' ] )->format( 'jS F Y H:i' ),
			]);
		}

		$outputDir = $this->outputPath ?? Config::get('trybe.output_path', storage_path('app/public/reports'));
		$destinationFile = rtrim($outputDir, '/') . '/Validation_Report.xlsx';

		(new FastExcel( $validationReports ))->configureOptionsUsing(function ($options) {
			if( method_exists( $options, 'setColumnWidth' ) ) {
				$options->setColumnWidth( 20, 1 );
				$options->setColumnWidth( 30, 2 );
				$options->setColumnWidth( 30, 3 );
			}
		})->export( $destinationFile );

		return $destinationFile;
	}
}