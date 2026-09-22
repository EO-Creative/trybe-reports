<?php

namespace App\Jobs;

use Illuminate\Support\Str;
use App\Services\TrybeService;
use Illuminate\Support\Carbon;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateDailyItineraries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?TrybeService $trybe;

    public ?string $outputPath;

    public function __construct(?string $outputPath = null)
    {
        $this->trybe = new TrybeService();
        $this->outputPath = $outputPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        $today = Carbon::today()->format( 'Y-m-d' );
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

        $itineraryData = [];
        foreach( $orderData as $order) {
            $itineraryData[$order[ 'order_ref' ]] = $this->trybe->getOrderItinerary( $order[ 'id' ] );
        }

        $guestItineraries = [];
        foreach( $itineraryData as $orderRef => $itinerary ) {
            $guestItineraries[$orderRef] = collect( $itinerary )->flatMap( function( array $entry ) use( $today, $orderRef ) {
                return collect( $entry['guests'] )->flatMap( function( string $guest ) use( $entry, $today, $orderRef ) {
                    $dateFilteredItems = collect( $entry['items'] )->filter(function( $dateGroup ) use( $today,  $orderRef ) {
                        $date = is_object($dateGroup) ? ( $dateGroup->date ?? null ) : ( $dateGroup['date'] ?? null );
                        if( $date !== null ) {
                            return Carbon::parse($date)->format('Y-m-d') === $today;
                        }
                        return true;
                    });

                    return $dateFilteredItems->map( function( array $itinerary ) use( $guest, $orderRef ) {
                        return [
                            'orderRef' => $orderRef ?? '',
                            'guest' => $guest,
                            'date' => Carbon::parse( $itinerary['date'] )->format( 'jS F Y' ),
                            'items' => collect( $itinerary['items'] )->map(function( $item ) {
                                return [
                                    'itemName' => Str::replace([ 'Residential', 'Treatment Inclusive' ], '', $item['name']),
                                    'startTime' => Carbon::parse($item['start_time'])->format( 'H:i' ),
                                    'endTime' => Carbon::parse($item['end_time'])->format( 'H:i' ),
                                ];
                            }),
                        ];
                    });
                });
            });
        }

        $itineraries = collect( $guestItineraries )->flatMap(function( $guest ) {
            return $guest;
        })->values()->all();

        $outputDir = $this->outputPath ?? Config::get('trybe.output_path', storage_path('app/public/reports'));
        $destinationFile = rtrim($outputDir, '/') . '/Daily-Itineraries.pdf';

        Pdf::view( 'pdfs.order-itinerary', compact( 'itineraries' ))->save( $destinationFile );

        return $destinationFile;
    }
}
