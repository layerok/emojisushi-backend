<?php
namespace Layerok\PosterPos\Classes\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use OFFLINE\Mall\Models\Product;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use poster\src\PosterApi;

class PosterDishesExport extends StringValueBinder implements FromCollection,
    WithHeadings, WithMapping, WithCustomValueBinder, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function collection()
    {
        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];
        PosterApi::init($config);
        $products = (array)PosterApi::menu()->getProducts([
            'type' => 'batchtickets'
        ]);

        return new Collection($products['response']);
    }

    public function map($product): array
    {
        return [
            $product->product_id,
            $product->product_name,
        ];
    }

    public function headings(): array
    {
        return [
            'Dish ID',
            'Имя',
            'Перевод'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],

        ];
    }


}

