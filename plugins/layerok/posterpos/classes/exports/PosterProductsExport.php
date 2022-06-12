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

class PosterProductsExport extends StringValueBinder implements FromCollection,
    WithHeadings, WithMapping, WithCustomValueBinder, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function collection()
    {
        PosterApi::init();
        $products = (array)PosterApi::menu()->getProducts();

        return new Collection($products['response']);
    }

    public function map($product): array
    {
        $with_modifications = isset($product->modifications) ? 1: 0;
        return [
            $product->product_id,
            $with_modifications,
            $product->product_name,
        ];
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'С модификаторами',
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

