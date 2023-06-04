<?php

namespace Layerok\PosterPos\Classes\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use poster\src\PosterApi;

class PosterProductImport implements ToModel
{
    public $check = false;
    // 0 - poster_id
    // 2 -  name
    // 3 - translate name
    public $updatedCount = 0;
    public $errors = [];
    public function model(array $row)
    {
        $id =  $row[0];
        $name = $row[1];
        $newName =  $row[2];

        if($id === 'Product ID') {
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

            $product = $res->response;

            $data = [
                'id' => $id,
                'product_id' => (int)$id,
                'product_name' => $newName,
                'modifications' => 0
            ];

            if(isset($product->modifications)) {
                $data['modifications'] = 1;
                $modifications = $product->modifications;
                foreach((array)$modifications as $modification) {
                    $data['modificator_id'][] = $modification->modificator_id;
                    $data['modificator_name'][] = $modification->modificator_name;

                }
            }

            $result = PosterApi::menu()->updateProduct($data);

            if(isset($result->error)) {
                $this->errors[$id][] = $result->message;
            } else {
                $this->updatedCount++;
            }

        }

    }
}
