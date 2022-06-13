<?php

namespace Layerok\PosterPos\Classes\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use poster\src\PosterApi;

class PosterDishImport implements ToModel
{
    public $check = false;
    // 0 - poster_id
    // 1 -  name
    // 2 - translate name
    public $updatedCount = 0;
    public $errors = [];
    public function model(array $row)
    {
        $id =  $row[0];
        $name = $row[1];
        $newName =  $row[2];

        if($id === 'Dish ID') {
            // Пропускаем ряд с названиями колонок
            $this->check = true;
            return;
        }

        if($this->check && $newName) {
            PosterApi::init();

            $res = (object)PosterApi::menu()->getProduct([
                'product_id' => $id
            ]);

            $product = $res->response;
            $ingredients = $product->ingredients;
            $arr_ingredients = (array)$ingredients;
            $ingredients_copy = [];
            foreach($arr_ingredients as $ingredient) {
                $ingredients_copy[] = [
                    'id' => (int)$ingredient->ingredient_id,
                    'type' => (int)$ingredient->structure_type,
                    'brutto' => (int)$ingredient->structure_brutto,
                    'netto' => (int)$ingredient->structure_netto,
                    'lock' => (int)$ingredient->structure_lock,
                    'clear' => 1
                ];
            }

            // todo: нужно еще модификаторы копировать, чтобы они не удалились

            $result = (object)PosterApi::menu()->updateDish([
                'dish_id' => $id,
                'product_name' => $newName,
                'weight_flag' => $product->weight_flag,
                'workshop' => $product->workshop,
                'menu_category_id' => $product->menu_category_id,
                'ingredient'=> $ingredients_copy
            ]);

            if(isset($result->error)) {
                $this->errors[$id][] = $result->message;
            } else {
                $this->updatedCount++;
            }

        }

    }
}
