<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Layerok\PosterPos\Classes\RootCategory;
use Layerok\PosterPos\Models\HideCategory;
use Layerok\PosterPos\Models\HideProduct;
use Layerok\PosterPos\Models\Spot;

use Layerok\Telegram\Models\Bot;
use Layerok\Telegram\Models\Chat;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\PropertyGroup;
use poster\src\PosterApi;
use Symfony\Component\Console\Input\InputOption;
use DB;

class ImportCategories extends Command {
    protected $name = 'poster:import-categories';
    protected $description = 'Fetch spots from PosterPos api and import into database';


    public function handle()
    {
        $question = 'All categories from Layerok.PosterPos will be erased. Do you want to continue?';
        if ( ! $this->option('force') && ! $this->output->confirm($question, false)) {
            return 0;
        }

        $this->output->newLine();
        $this->output->writeln('Deleting categories...');
        $this->output->newLine();

        Category::truncate();
        HideCategory::truncate();
        DB::table('offline_mall_category_product')->truncate();
        DB::table('offline_mall_category_property_group')->truncate();
        Artisan::call('cache:clear');


        $this->output->newLine();
        $this->output->writeln('Creating categories...');
        $this->output->newLine();

        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];

        PosterApi::init($config);
        $categories = (object)PosterApi::menu()->getCategories();

        $this->output->progressStart(count($categories->response));

        $root = Category::create([
            'name' => 'Меню',
            'slug'          => RootCategory::SLUG_KEY,
            'poster_id'     => 0,
            'sort_order'    => 0,
        ]);

        $ids = PropertyGroup::all()->pluck('id');
        $root->property_groups()->attach($ids);

        foreach ($categories->response as $category) {
            $poster_id = $category->category_id;
            $slug = $category->category_tag ?? str_slug($category->category_name);

            $parent = Category::where('poster_id', $category->parent_category)->first();

            $publish = $category->category_hidden === '0' ? 1: 0;

            $categoryModel = Category::create([
                'name'          => (string)$category->category_name,
                'slug'          => $slug,
                'poster_id'     => (int)$poster_id,
                'sort_order'    => (int)$category->sort_order,
                'parent_id'     => $parent->id,
                'published'     => $publish
            ]);


            if(isset($category->visible)) {
                foreach($category->visible as $spot) {
                    $spotModel = Spot::where('poster_id', $spot->spot_id)->first();
                    if(!$spotModel) {
                        continue;
                    }

                    if(!$spot->visible) {
                        HideCategory::create([
                            'spot_id' => $spotModel->id,
                            'category_id' => $categoryModel->id
                        ]);
                    } else {
                        HideCategory::where([
                            'spot_id' => $spotModel->id,
                            'category_id' => $categoryModel->id
                        ])->delete();
                    }

                }
            }


            // Привязываем к категории группу фильтров "Ингридиенты"
            $categoryModel->property_groups()->attach($ids);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->output->success('All done!');
    }

    protected function getArguments()
    {
        return [];
    }

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Don\'t ask before deleting the data.', null],
        ];
    }






}
