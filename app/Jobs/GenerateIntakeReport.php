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

class GenerateIntakeReport implements ShouldQueue
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

		$intakeForms = collect([]);
		foreach( $orderData as $order ) {
			$intakeForms->push([
				'Order Reference' => $order[ 'order_ref' ],
				'Customer Name' => sprintf( '%s %s', $order[ 'first_name' ], $order[ 'last_name' ] ),
				'Customer Email' => $order[ 'email' ] ?? '',
				'Customer Phone' => $order[ 'phone' ] ?? '',
				'Intake Form Status' => $order[ 'intake_form_required' ] ? ( $order[ 'intake_forms_complete' ] ? 'Complete' : 'Not Complete' ) : 'Not Required'
			]);
		}

		$outputDir = $this->outputPath ?? Config::get('trybe.output_path', storage_path('app/public/reports'));
		$destinationFile = rtrim($outputDir, '/') . '/Intake_Forms_Report.xlsx';

		(new FastExcel( $intakeForms ))->export( $destinationFile );

		return $destinationFile;
	}
}