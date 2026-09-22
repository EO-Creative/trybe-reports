<?php

namespace App\Jobs;

use App\Services\TrybeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class GenerateProductsCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?TrybeService $trybe;

    public ?string $outputPath;

    public function __construct(?string $outputPath = null)
    {
        $this->trybe = new TrybeService();
        $this->outputPath = $outputPath;
    }

    public function handle(): string
    {
        $allProducts = array();

        $page = 1;
        $query = array();
        $perPage = $query['per_page'] ?? 10;
        $query['per_page'] = $perPage;
        $query['page'] = $page;

        $firstPageData = $this->trybe->getProducts($query);
        $lastPage = $firstPageData['meta']['last_page'] ?? 1;

        $allProducts = array_merge($allProducts, $firstPageData['data'] );

        if( $lastPage > 1 ) {
            for( $p = 2; $p <= $lastPage; $p++ ) {
                $query['page'] = $p;
                $pageData = $this->trybe->getProducts($query);

                if( !empty( $pageData['data'] ) ) {
                    $allProducts = array_merge($allProducts, $pageData['data'] );
                }
            }
        }

        $csvData = [
            ['ID', 'Name', 'Revenue Centre', 'Barcode', 'Selling Price', 'Updated at', 'Deleted at']
        ];

        foreach( $allProducts as $product ) {
            $sellingPrice = $product['price_rules'][0]['price'] ?? '';
            $csvData[] = [
                $product[ 'id' ] ?? '',
                $product[ 'name' ] ?? '',
                $product[ 'revenue_centre' ] ?? '',
                $product[ 'barcode' ] ?? '',
                $sellingPrice,
                $product[ 'updated_at' ] ?? '',
                $product[ 'deleted_at' ] ?? '',
            ];
        }

        $outputDir = $this->outputPath ?? Config::get('trybe.output_path', storage_path('app/public/reports'));

        if( !File::exists( $outputDir ) ) {
            File::makeDirectory( $outputDir, 0755, true );
        }

        $destinationFile = rtrim($outputDir, '/') . '/products-data.csv';
        $file = fopen($destinationFile, 'w');

        foreach( $csvData as $row ) {
            fputcsv( $file, $row );
        }

        fclose($file);

        return $destinationFile;
    }
}
