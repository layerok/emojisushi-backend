<?php
namespace Layerok\PosterPos\Controllers;

use BackendMenu;
use Input;
use Layerok\PosterPos\Classes\Imports\PosterCategoryImport;
use Layerok\PosterPos\Classes\Imports\PosterDishImport;
use Layerok\PosterPos\Classes\Imports\PosterIngredientImport;
use Layerok\PosterPos\Classes\Imports\PosterProductImport;
use Maatwebsite\Excel\Facades\Excel;


class Import extends \Backend\Classes\Controller
{
    public function __construct() {
        parent::__construct();

        BackendMenu::setContext('Layerok.PosterPos', 'import');
    }

    public function index()
    {

    }

    public function items() {
        ini_set('max_execution_time', 18000);
        set_time_limit(0);
        $map = [
            'product' => PosterProductImport::class,
            'category' => PosterCategoryImport::class,
            'ingredient' => PosterIngredientImport::class,
            'dish' => PosterDishImport::class,
        ];

        $type = input('type');
        $exist = in_array($type, array_keys($map));

        if (Input::hasFile('file')) {

            if($type && $exist) {

                $class = $map[$type];
                $instance = new $class();
                $file = Input::file('file');
                Excel::import($instance, $file);

                $messages = [];
                if(!$instance->check) {
                    $messages[] = "Не подходящий файл для типа " . $type;
                }
                if(count($instance->errors) > 0) {
                    foreach($instance->errors as $id => $errors) {
                        foreach ($errors as $error) {
                            $messages[] = "Ошибка при импортировании товара c id [" . $id . "]. " . $error;
                        }
                    }
                }
                $messages[] = "Обновлено " . $instance->updatedCount . " записей типа " . $type;


                $html = "<ul>";

                foreach ($messages as $message) {
                    $html .= "<li>" . $message . "</li>";
                }

                $html .= "<ul/>";

                return $html;
            }

        } else {
            return "Provide excel file to import";
        }


    }


}
