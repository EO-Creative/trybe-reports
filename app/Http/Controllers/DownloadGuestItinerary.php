<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Services\TrybeService;
use Illuminate\Support\Carbon;
use function Spatie\LaravelPdf\Support\pdf;

class DownloadGuestItinerary extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $orderId)
    {
        $trybe = new TrybeService();

        $order = $trybe->getOrder($orderId);
        $itinerary = $trybe->getOrderItinerary($orderId);

        $orderRef = $order['order_ref'];
        $fileName = "{$orderRef} Order Itinerary.pdf";

        $itineraries = collect( $itinerary )->flatMap( function( array $entry ) use( $orderRef ) {
            return collect( $entry['guests'] )->flatMap( function( string $guest ) use( $entry, $orderRef ) {
                return collect( $entry['items'] )->map( function( array $itinerary ) use( $guest, $orderRef ) {
                    return [
                        'orderRef' => $orderRef,
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
        })->values()->all();

        return pdf('pdfs.order-itinerary', compact( 'itineraries' ) )->name( $fileName );
    }
}
