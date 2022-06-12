<?php

namespace Layerok\PosterPos\Classes;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Observers\ProductObserver;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\ImageSet;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\PropertyValue;
use OFFLINE\Mall\Models\Variant;
use System\Models\File;

class PosterTransition
{
    public function createProduct($value)
    {
        $productExist = Product::where('poster_id', '=', $value->product_id)->first();

        if ($productExist) {
            // Если товар с таким poster_id существует, то нужно обновить товара,
            // но пока мы просто выходим
            return;
        }

        $product = Product::create([
            'name' => (string)$value->product_name,
            'slug' => (string)str_slug($value->product_name),
            'poster_id' => (int)$value->product_id,
            'user_defined_id' => (int)$value->product_id,
            'weight'  => (int)$value->out,
            'allow_out_of_stock_purchases' => 1,
            'published' => (int)$value->hidden == "0" ? 1: 0,
            'stock' => 9999999,
            'inventory_management_method' => 'single',
        ]);

        // После создания товара нужно связать товар с категорией
        // 1. Найдем категорию к которой нужно привязать товар
        $category = Category::where('poster_id', '=', $value->menu_category_id)->first();
        // 2. Привяжем категорию к товару
        if (!empty($category)) {
            $product->categories()->detach($category['id']);
            $product->categories()->sync([$category['id'] => ['sort_order' => (int)$value->sort_order]]);
        }

        $currency = Currency::where('code', '=', 'UAH')->first();

        if(!$currency) {
            // Если не существует гривневой валюты, то создаем
            $currency = Currency::create([
                'code'     => 'UAH',
                'format'   => '{{ currency.symbol }} {{ price|number_format(2, ".", ",") }}',
                'decimals' => 2,
                'is_default' => true,
                'symbol'   => '₴',
                'rate'     => 1,
            ]);
        }



        // Добавим цену товару
        // Нужно учесть две ситуации, когда мы имеем дела с товаров и когда с тех картой
        if (isset($value->modifications)) {
            // Товар
            $product->inventory_management_method = 'variant';
            $product->save();

            // Создадим свойства, которые можно будет выбрать при покупке товара
            $property = Property::create([
                'name' => $value->product_name,
                'slug' => str_slug($value->product_name) . "_mod",
                'type' => 'dropdown',
            ]);


            $options = [];
            foreach ($value->modifications as $mod) {
                $options[] = ['value' => $mod->modificator_name];

                // Создадим вариант для этого свойства
                $variant = Variant::create([
                    'name' => $value->product_name . " " . $mod->modificator_name,
                    'product_id' => $product['id'],
                    'stock' => 99999,
                    'published' => 1,
                    'allow_out_of_stock_purchases' => 1,
                    'poster_id' => $mod->modificator_id
                ]);

                // Создадим цену для варианта
                ProductPrice::create([
                    'price' => substr($mod->spots[0]->price, 0, -2),
                    'product_id' => $product['id'],
                    'variant_id' => $variant['id'],
                    'currency_id' => $currency->id,
                ]);

                // Привяжем к варианту свойство
                PropertyValue::create([
                    'product_id' => $product['id'],
                    'variant_id' => $variant['id'],
                    'property_id' => $property['id'],
                    'value' => $mod->modificator_name
                ]);

            }

            $property->options = $options;
            $property->save();


            // Создадим группу свойств куда будет помещать модификаторы
            $property_group_name = 'Модификаторы для товара ' . $value->product_name;

            $property_group = PropertyGroup::create([
                'name' => $property_group_name,
                'slug' => str_slug($property_group_name),
            ]);
            // Привяжем свойство к группе свойств модификаторов
            $property->property_groups()->attach($property_group['id'], ['use_for_variants' => 1]);





            //Создадим дочернию категорию, чтобы ограничить кол-во менеямых свойств
            $child_category = Category::create([
                'name'          => (string)$value->product_name,
                'parent_id'     => $category['id'],
                'slug'          => str_slug($value->product_name),
            ]);
            if (!empty($child_category)) {
                $product->categories()->detach($child_category['id']);
                $product->categories()->sync([$child_category['id'] => ['sort_order' => (int)$value->sort_order]]);
            }

            //Привяжем группу свойств к категории товаров
            $child_category->property_groups()->attach($property_group['id']);



        }
        else {
            // Тех. карта
            ProductPrice::create([
                'price' => (int)substr($value->price->{'1'}, 0, -2),
                'product_id' => $product['id'],
                'currency_id' => $currency->id,
            ]);

            if (isset($value->ingredients)) {
                foreach ($value->ingredients as $key => $i) {
                    $property = Property::where('poster_id', $i->ingredient_id)->first();


                    $name = preg_replace('/\s+ПФ/', '', $i->ingredient_name);

                    $disallow = preg_match('/Бокс\s+д\/суши\s+большой/', $name);

                    if ($disallow) {
                        continue;
                    }

                    if($property) {
                        PropertyValue::create([
                           'product_id' => $product->id,
                           'property_id' => $property->id,
                           'value' => $name
                        ]);
                    }

                }

                $product->save();
            }
        }

        if (!empty($value->photo)) {
            try {
                $url = env('POSTER_URL') . (string)$value->photo;

                $image_set = ImageSet::create([
                    'name' => $product['name'],
                    'is_main_set' => 1,
                    'product_id' => $product['id'],
                ]);

                $file = new File();
                $file->fromUrl($url);

                if (!isset($file)) {
                    return;
                }

                $image_set->images()->add($file);
            } catch (\Exception $e) {
                echo 'Caught error', $e->getMessage(), "\n";
            }

        }


        // Это какая-то переиндексация, короче если это не сделать, то товара не будет отображен пользователю
        $observer = new ProductObserver(app(Index::class));
        Product::where('id', '=', $product['id'])->orderBy('id')->with([
            'variants.prices.currency',
            'prices.currency',
            'property_values.property',
            'categories',
            'variants.prices.currency',
            'variants.property_values.property',
        ])->chunk(200, function (Collection $products) use ($observer) {
            $products->each(function (Product $product) use ($observer) {
                $observer->created($product);
            });
        });
    }

    public function deleteProduct($id)
    {
        $product = Product::where('poster_id', '=', $id)->first();
        if ($product) {
            (new ProductObserver(app(Index::class)))->deleted($product);
            $product->delete();
        }
    }

    public function updateProduct($value)
    {

        $product = Product::where('poster_id', '=', $value->product_id)->first();
        if (!$product) {
            // Если такого товара не существует, то выходим
            return;
        }

        $product->update([
            'name' => (string)$value->product_name,
            'weight'  => (int)$value->out,
            'allow_out_of_stock_purchases' => 1,
            'published' => (int)$value->spots[0]->visible,
        ]);

        // 1. Найдем категорию к которой нужно привязать товар
        $category = Category::where('poster_id', '=', $value->menu_category_id)->first();
        // 2. Привяжем категорию к товару
        if (!empty($category)) {
            $product->categories()->detach();
            $product->categories()->sync([$category['id'] => ['sort_order' => (int)$value->sort_order]]);
        }

        // Добавим цену товару
        // Нужно учесть две ситуации, когда мы имеем дела с товаров и когда с тех картой
        if (isset($value->modifications)) {
            //todo обновление товара
        }
        else {
            // Тех. карта
            $price = ProductPrice::where('product_id', '=', $product['id'])->first();
            if ($price) {
                $price->update([
                    'price' => (int)substr($value->price->{'1'}, 0, -2),
                ]);
            }
//            $product->description = "";
//            if (isset($value->ingredients)) {
//
//                foreach ($value->ingredients as $key => $i) {
//                    $ingr = $i->ingredient_name;
//                    $disallow = preg_match('/Бокс\s+д\/суши\s+большой/', $ingr);
//
//                    if ($disallow) {
//                        continue;
//                    }
//
//                    $ingr = preg_replace('/\s+ПФ/', '', $i->ingredient_name);
//                    $product->description .= $ingr;
//                    if (count($value->ingredients) != $key + 1) {
//                        $product->description .= ", ";
//                    }
//                }
//            }
//            $product->save();
        }


        $image_sets = ImageSet::where('product_id', '=', $product['id'])->get();
        if ($image_sets) {
            $files = File::whereIn('attachment_id', $image_sets->pluck('id'))->get();
            if ($files) {
                foreach ($files as $file) {
                    $file->delete();
                }
            }
            foreach ($image_sets as $set) {
                $set->delete();
            }

        }

        if (!empty($value->photo)) {

            $url = env('POSTER_URL') . (string)$value->photo;

            $image_set = ImageSet::create([
                'name' => $product['name'],
                'is_main_set' => 1,
                'product_id' => $product['id'],
            ]);

            $file = new File;
            $file->fromUrl($url);

            if (!isset($file)) {
                return;
            }

            $image_set->images()->add($file);
        }




        // Реиндексация
        $observer = new ProductObserver(app(Index::class));
        Product::where('id', '=', $product['id'])->orderBy('id')->with([
            'variants.prices.currency',
            'prices.currency',
            'property_values.property',
            'categories',
            'variants.prices.currency',
            'variants.property_values.property',
        ])->chunk(200, function (Collection $products) use ($observer) {
            $products->each(function (Product $product) use ($observer) {
                $observer->updated($product);
            });
        });



    }
}
