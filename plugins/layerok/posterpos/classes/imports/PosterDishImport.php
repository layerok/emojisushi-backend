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
            $config = [
                'access_token' => config('poster.access_token'),
                'application_secret' => config('poster.application_secret'),
                'application_id' => config('poster.application_id'),
                'account_name' => config('poster.account_name')
            ];
            PosterApi::init($config);

            $res = (object)PosterApi::menu()->getProduct([
                'product_id' => $id
            ]);

            if(!isset($res->response)) {
                $this->errors[$id][] = "Тех. карта не найдена под id [$id}]";
                return;
            }

            $product = $res->response;
            $ingredients = $product->ingredients;

            $arr_ingredients = (array)$ingredients;
            $ingredients_copy = [];
            $modificationgroup_copy = [];

            if(isset($product->group_modifications)) {
                foreach($product->group_modifications as $group) {
                    $modifications_copy = [];
                    foreach($group->modifications as $modification) {
                        $modifications_copy[] = [
                            'dish_modification_id' => $modification->dish_modification_id,
                            'ingredientId' => $modification->ingredient_id,
                            'type' => $modification->type,
                            'name' => $modification->name,
                            'brutto' => $modification->brutto,
                            'price' => $modification->price,
                        ];
                    }
                    $modificationgroup_copy[] = [
                        'dish_modification_group_id' => $group->dish_modification_group_id,
                        'type' => $group->type,
                        'minNum' => $group->num_min,
                        'maxNum' => $group->num_max,
                        'name' => $group->name,
                        'modifications' => $modifications_copy
                    ];
                }
            }



            foreach($arr_ingredients as $ingredient) {
                $ingredients_copy[] = [
                    'id' => (int)$ingredient->ingredient_id,
                    'type' => (int)$ingredient->structure_type,
                    'brutto' => (int)$ingredient->structure_brutto,
                    'netto' => (int)$ingredient->structure_netto,
                    'lock' => (int)$ingredient->structure_lock,
                    'clear' => (int)$ingredient->pr_in_clear,
                    'fry' => (int)$ingredient->pr_in_fry,
                    'stew' => (int)$ingredient->pr_in_stew,
                    'bake' => (int)$ingredient->pr_in_bake,
                    'cook' => (int)$ingredient->pr_in_cook,

                ];
            }

            $result = (object)PosterApi::menu()->updateDish([
                'dish_id' => $id,
                'product_name' => $newName,
                'weight_flag' => $product->weight_flag,
                'workshop' => $product->workshop,
                'menu_category_id' => $product->menu_category_id,
                'ingredient'=> $ingredients_copy,
                'modificationgroup'=> $modificationgroup_copy
            ]);

            if(isset($result->error)) {
                $this->errors[$id][] = $result->message;
            } else {
                $this->updatedCount++;
            }

        }

    }
}
