<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class TrybeService
{
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected ?string $siteId;
    protected ?PendingRequest $httpClient = null;

    public function __construct( ?PendingRequest $httpClient = null )
    {
        $this->baseUrl = Config::get('services.trybe.base_url');
        $this->apiKey = Config::get('services.trybe.api_key');
        $this->siteId = Config::get('services.trybe.site_id');
        $this->httpClient = $httpClient;
    }

    public function client(): PendingRequest
    {
        if( $this->httpClient !== null ) {
            return $this->httpClient;
        }

        return Http::baseUrl( $this->baseUrl )->withToken( $this->apiKey )->acceptJson();
    }

    /**
     * @param array $query
     * @return Collection
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getOrders( array $query ): Collection
    {
        $response = $this->client()->get('shop/orders', array_merge([
            'site_id' => $this->siteId,
        ], $query));

        return $response->collect() ?? collect([]);
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @return Collection
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getOrdersByDate( string $dateFrom, string $dateTo, $query = array() ): Collection
    {
        return $this->getOrders(array_merge( $query, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));
    }

    /**
     * @return Collection
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getTodaysOrders( array $query = array() ): Collection
    {
        return $this->getOrdersByDate(
            Carbon::today()->format( 'Y-m-d' ),
            Carbon::today()->format( 'Y-m-d' ),
            $query
        );
    }

    public function getTomorrowsOrders( array $query = array() ): Collection
    {
        return $this->getOrdersByDate(
            Carbon::tomorrow()->format( 'Y-m-d' ),
            Carbon::tomorrow()->format( 'Y-m-d' ),
            $query
        );
    }

    /**
     * @param string $orderId
     * @return array
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getOrder( string $orderId ): array
    {
        return $this->client()->get("shop/orders/{$orderId}")->collect( 'data' )->toArray();
    }

    /**
     * @param string $orderId
     * @return array
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getOrderItinerary( string $orderId ): array
    {
        return $this->client()->get("shop/orders/{$orderId}/itinerary")->collect('data')->toArray();
    }

    /**
     * @return array
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getProducts( array $query = array() ): array
    {
        return $this->client()->get('shop/products', array_merge( $query, [
          'site_id' => $this->siteId
        ]))->collect()->toArray();
    }

    /**
     * @param string $productId
     * @return array
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function getProduct( string $productId ): array
    {
        return $this->client()->get("shop/products/{$productId}")->collect('data')->toArray();
    }
}
