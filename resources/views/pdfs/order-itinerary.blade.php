<html lang="en">
    <head>
        <title>Carden Park Spa Itinerary</title>
        @vite(['resources/css/pdf.css'])
    </head>
    <body>
        @foreach( $itineraries as $itinerary )
            <div class="block h-15"></div>
            <div class="max-w-1/2">
                <div class="px-4 pb-1">
                    <p class="text-xs">Order Reference: {{ $itinerary['orderRef'] }}</p>
                </div>
                <div class="bg-spa-light-stone px-4 py-2 mb-4">
                    <div class="flex flex-row gap-x-4">
                        <div class="flex-1">
                            <p>{{ $itinerary['guest'] }}</p>
                        </div>
                        <div class="flex-1">
                            <p class="text-right">{{ $itinerary[ 'date' ] }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-2">
                    <div class="mb-2">Your Spa Experience</div>
                    @foreach( $itinerary[ 'items' ] as $item )
                        <div class="flex flex-row gap-x-2">
                            <div class="flex-initial min-w-max">
                                <p class="text-sm font-bold">
                                    {{ $item[ 'startTime'] }} - {{ $item[ 'endTime'] }}:
                                </p>
                            </div>
                            <div class="text-sm flex-auto">
                                {{ $item[ 'itemName' ] }}
                            </div>
                        </div>
                    @endforeach
                </div>
                @pageBreak
            </div>
        @endforeach
    </body>
</html>
