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
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use poster\src\PosterApi;

class PosterIngredientsExport extends StringValueBinder implements FromCollection,
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
        $records= (array)PosterApi::menu()->getIngredients();

        return new Collection($records['response']);
    }

    public function map($record): array
    {
        return [
            $record->ingredient_id,
            $record->ingredient_unit,
            $record->ingredient_name,
        ];
    }

    public function headings(): array
    {
        return [
            'Ingredient ID',
            'Unit type',
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

