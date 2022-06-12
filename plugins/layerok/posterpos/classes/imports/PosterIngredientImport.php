<?php

namespace Layerok\PosterPos\Classes\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use poster\src\PosterApi;

class PosterIngredientImport implements ToModel
{
    public $check = false;
    // 0 - poster_id
    // 1 - name
    // 2 - translate name
    public $updatedCount = 0;
    public $errors = [];
    public function model(array $row)
    {
        $id =  $row[0];
        $newName =  $row[2];

        if($id === 'Ingredient ID') {
            // Пропускаем ряд с названиями колонок
            $this->check = true;
            return;
        }

        if($this->check && $newName) {
            PosterApi::init();
            $result = PosterApi::menu()->updateIngredient([
                'id' => $id,
                'ingredient_name' => $newName
            ]);
            if(isset($result->error)) {
                $this->errors[$id][] = $result->message;
            } else {
                $this->updatedCount++;
            }
        }

    }
}
