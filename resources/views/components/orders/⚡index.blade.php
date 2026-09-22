<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;


use App\Services\TrybeService;

new class extends Component {
    use WithPagination;

	protected ?TrybeService $trybe;

    public array $perPageOptions = [
        10 => '10',
        25 => '25',
        50 => '50',
        100 => '100'
    ];

    #[Url]
	public ?int $page = 1;

	#[Url]
    public ?int $perPage = null;

	#[Url]
	public ?string $dateFrom;

	#[Url]
	public ?string $dateTo;

    public function boot(TrybeService $trybe): void
    {
        $this->trybe = $trybe;
    }

    public function mount(): void
    {
        $this->dateFrom ??= Carbon::today()->format('Y-m-d');
        $this->dateTo ??= Carbon::today()->format('Y-m-d');
    }

	#[Computed]
	public function orders(): LengthAwarePaginator
	{
        $page = $this->getPage();

        $query = [
            'page' => $page,
            'item_date_from' => $this->dateFrom,
            'item_date_to' => $this->dateTo,
        ];

        if( !is_null( $this->perPage ) ) {
            $query[ 'per_page' ] = $this->perPage;
        }

        $response = $this->trybe->getOrders( $query );

        $meta = $response->get('meta');

        $this->perPageOptions[ $meta[ 'per_page' ] ] = (string) $meta[ 'per_page' ];
        asort($this->perPageOptions);

        if( is_null( $this->perPage ) ) {
            $this->perPage = $meta[ 'per_page' ];
        }

        $orders = collect( $response->get( 'data' ) )->values()->map(function( array $order ): array {
            $leadBooker = collect( $order[ 'guests' ] )->firstWhere( 'is_lead_booker', true );

            $leadBooker ??= [
                'name' => Arr::join([
                    $order['first_name'],
                    $order['last_name'],
                ], ' ')
            ];

            $guests = collect($order[ 'guests' ])->pluck( 'name' )->join(', ');

            return [
                'id' => $order[ 'id' ],
                'orderRef' => $order[ 'order_ref' ],
                'leadBooker' => $leadBooker,
                'guests' => $guests,
                'booking_items_start_date' => $order[ 'booking_items_start_date' ]
            ];
        });

        return new LengthAwarePaginator(
            items: $orders,
            total: $meta['total'],
            perPage: $meta['per_page'],
            currentPage: $meta['current_page'],
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );
	}

    public function rules(): array
    {
        return [
            'dateFrom' => [ 'required', 'date_format:Y-m-d', 'before_or_equal:dateTo'],
            'dateTo' => [ 'required', 'date_format:Y-m-d', 'after_or_equal:dateFrom']
        ];
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(?string $value): void
    {
        if( blank( $value ) || blank( $this->dateTo ) ) {
            return;
        }

        $dateFrom = Carbon::parse( $value );
        $dateTo = Carbon::parse( $this->dateTo );

        if( $dateFrom->isAfter( $dateTo ) ) {
            $this->dateTo = $value;
        }
    }

    public function updatedDateTo( ?string $value ): void
    {
        if( blank( $value ) || blank( $this->dateFrom ) ) {
            return;
        }

        $dateFrom = Carbon::parse( $this->dateFrom );
        $dateTo = Carbon::parse( $value );

        if( $dateTo->isBefore( $dateFrom ) ) {
            $this->dateFrom = $value;
        }
    }

    public function searchOrders(): void
    {
        $this->validate();
        $this->resetPage();
        unset($this->orders);
    }

    public function generateItinerary($orderId): void
    {
        dump( $orderId );
    }
};
?>

<div class="pb-10">
    <div class="sticky top-0 z-999">
        <div class="pt-10 bg-spa-light-stone"></div>
        <div class="xl:container xl:mx-auto px-4 md:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-md shadow mb-6">
                <form wire:submit.stop="searchOrders">
                    <div class="grid gap-x-6 lg:grid-cols-3 lg:gap-x-6 lg:items-end">
                        <div>
                            <label for="dateFrom" class="text-sm font-semibold">From Date</label>
                            <input class="block w-full border px-6 py-3 border-spa-blue" type="date" value="{{ $this->dateFrom }}" wire:model.renderless.live="dateFrom" />
                            @error('dateFrom')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="dateFrom" class="text-sm font-semibold">To Date</label>
                            <input class="block w-full border px-6 py-3 border-spa-blue" type="date" value="{{ $this->dateFrom }}" wire:model.renderless.live="dateTo" />
                            @error('dateTo')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <button class="block w-full px-6 py-3 text-spa-blue bg-spa-green border border-spa-green cursor-pointer">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="xl:container xl:mx-auto px-4 md:px-6 lg:px-8">
        <div class="relative bg-white p-6 rounded-md shadow mb-6">
            <div class="absolute flex inset-0 w-full h-full bg-black/25" wire:loading>
                <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="block size-15 animate-spin text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>
            </div>
            <table class="table-fixed w-full divide-y divide-spa-light-stone">
                <thead>
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-3">
                            Order Reference
                        </th>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-3">
                            Lead Booker
                        </th>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-3">
                            Spa Guests
                        </th>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-3">
                            <span class="sr-only">Order Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach( $this->orders as $order )
                        <tr class="bg-white even:bg-spa-light-stone" wire:key="order-{{ $order[ 'id' ] }}">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-3">
                                {{ $order[ 'orderRef' ] }}
                            </td>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-3">
                                {{ $order[ 'leadBooker' ][ 'name' ] }}
                            </td>
                            <td class="whitespace-normal py-4 pl-4 pr-3 sm:pl-3">
                                {{ $order[ 'guests' ] }}
                            </td>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-3">
                                <a class="block text-center text-spa-blue px-6 py-3 border border-spa-blue cursor-pointer hover:text-white hover:bg-spa-blue transition-colors" href="{{ route( 'download-guest-itinerary', $order[ 'id' ] ) }}" target="_blank">
                                    Generate Itinerary
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="sticky bottom-0">
            <div class="relative bg-white p-6 rounded-md shadow">
                <div class="xl:container">
                    <div class="flex flex-row gap-x-4 justify-between items-center">
                        <div class="flex-inital">
                            <select class="px-4 py-3 bg-spa-green" wire:model.live="perPage">
                                @foreach($this->perPageOptions as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-auto">
                            {{ $this->orders->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
