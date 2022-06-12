<?php

namespace Layerok\PosterPos\Classes\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use poster\src\PosterApi;

class PosterProductImport implements ToModel
{
    public $check = false;
    // 0 - poster_id
    // 1 - С модификаторами
    // 2 -  name
    // 3 - translate name
    public $updatedCount = 0;
    public $errors = [];
    public function model(array $row)
    {
        $id =  $row[0];
        $newName =  $row[3];
        $with_modifications = $row[1];

        if($id === 'Product ID') {
            // Пропускаем ряд с названиями колонок
            $this->check = true;
            return;
        }

        if($this->check && $newName) {
            PosterApi::init();
            $result = PosterApi::menu()->updateProduct([
                'id' => $id,
                'product_id' => (int)$id,
                'product_name' => (int)$newName,
                'modifications' => $with_modifications ? 1: 0
            ]);
            if(isset($result->error)) {
                if($result->error === 32) {
                    $result = PosterApi::menu()->updateProduct([
                        'dish_id' => $id,
                        'product_name' => (int)$newName,
                    ]);

                    if($result->error) {
                        $this->errors[$id][] = $result->message;
                    }
                } else {
                    $this->errors[$id][] = $result->message;
                }

            } else {
                $this->updatedCount++;
            }

        }

    }
}
