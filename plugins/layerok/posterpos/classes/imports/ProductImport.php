<?php

namespace Layerok\PosterPos\Classes\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use OFFLINE\Mall\Models\Product;
use poster\src\PosterApi;

class ProductImport implements ToModel
{
    public $check = false;
    public $updatedCount = 0;
    public $errors = [];
    public $rowIndex = 0;

    // 0 - Краткое описание
    // 1 - poster_id
    public function model(array $row)
    {
        $poster_id =  $row[1];
        $description_short = $row[0];
        if($this->rowIndex === 0) {
            $this->rowIndex++;
            return;
        }

        if($description_short && $poster_id) {

            $product = Product::where('poster_id', $poster_id)->first();
            $product->description_short = $description_short;
            $product->save();

        }
        $this->rowIndex++;

    }
}
