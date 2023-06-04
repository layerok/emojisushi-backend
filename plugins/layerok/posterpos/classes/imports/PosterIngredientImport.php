<?php

namespace Layerok\PosterPos\Classes\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use poster\src\PosterApi;

class PosterIngredientImport implements ToModel
{
    public $check = false;
    // 0 - poster_id
    // 1 - unit
    // 2 -  name
    // 3 - translate name
    public $updatedCount = 0;
    public $errors = [];
    public function model(array $row)
    {
        $id =  $row[0];
        $unit = $row[1];
        $newName =  $row[3];

        if($id === 'Ingredient ID') {
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
            $result = PosterApi::menu()->updateIngredient([
                'id' => $id,
                'ingredient_name' => $newName,
                'type' => $unit
            ]);
            if(isset($result->error)) {
                $this->errors[$id][] = $result->message;
            } else {
                $this->updatedCount++;
            }
        }

    }
}
