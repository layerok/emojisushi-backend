<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Layerok\PosterPos\Classes\IngredientsGroup;
use Layerok\PosterPos\Classes\PosterTransition;
use Layerok\PosterPos\Classes\RootCategory;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Index\Noop;
use OFFLINE\Mall\Classes\Index\ProductEntry;
use OFFLINE\Mall\Classes\Index\VariantEntry;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\ImageSet;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\Price;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\PropertyValue;
use OFFLINE\Mall\Models\ShippingMethod;
use OFFLINE\Mall\Models\Variant;
use poster\src\PosterApi;
use Symfony\Component\Console\Input\InputOption;
use DB;

class ImportIngredients extends Command {
    protected $name = 'poster:import-ingredients';
    protected $description = 'Fetch ingredients from PosterPos api and import into database';
    protected $group = null;

    public function handle()
    {
        $question = 'All existing OFFLINE.Mall properties will be erased. Do you want to continue?';
        if ( ! $this->option('force') && ! $this->output->confirm($question, false)) {
            return 0;
        }

        // Use a Noop-Indexer so no unnecessary queries are run during seeding.
        // the index will be re-built once everything is done.
        $originalIndex = app(Index::class);
        app()->bind(Index::class, function () {
            return new Noop();
        });


        $this->cleanup();
        $this->create();


        app()->bind(Index::class, function () use ($originalIndex) {
            return $originalIndex;
        });

        if($this->option('reindex')) {
            Artisan::call('mall:reindex', ['--force' => true]);
        }

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
            ['reindex', null, InputOption::VALUE_NONE, 'Reindex after importing ingredients', null],
        ];
    }

    protected function cleanup()
    {
        $this->output->writeln('Removing existing ingredients and more...');

        $this->output->writeln('deleting property group for ingredients');
        PropertyGroup::truncate();
        $this->output->writeln('deleting properties in property group for ingredients');
        Property::truncate();
        $this->output->writeln('deleting property values for property in property group for ingredients');
        PropertyValue::truncate();

        $this->output->writeln('detaching property group from properties and categories');
        DB::table('offline_mall_property_property_group')
            ->truncate(); // detach properties from property group
        DB::table('offline_mall_category_property_group')
            ->truncate(); // detach categories from property group


        if($this->option('reindex')) {
            $index = app(Index::class);
            $index->drop(ProductEntry::INDEX);
            $index->drop(VariantEntry::INDEX);
        }
    }

    protected function create() {
        $this->output->newLine();
        $this->output->writeln('Creating ingredients...');
        $this->output->newLine();

        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];

        PosterApi::init($config);

        $this->createIngredientCategories();


    }

    protected function createIngredientCategories() {
        $this->output->newLine();
        $this->output->newLine();
        $this->output->writeln('Creating ingredient categories');
        $ingredients = (object)PosterApi::menu()->getIngredients();
        $categories = (object)PosterApi::menu()->getCategoriesIngredients();

        $count = count($categories->response);

        $this->output->progressStart($count);

        foreach ($categories->response as $category) {
            $category_id = $category->category_id;

            $group = PropertyGroup::create([
                'name' => $category->name,
                'poster_id' => $category_id
            ]);

            foreach($ingredients->response as $ingredient) {
                if((int)$category_id === $ingredient->category_id) {

                    $property = Property::create([
                        'type' => 'checkbox',
                        'poster_id' => $ingredient->ingredient_id,
                        'name' => $ingredient->ingredient_name,
                    ]);
                    $group->properties()->attach($property->id, [
                        'filter_type' => 'set',
                        'use_for_variants' => false
                    ]);
                }
            }


            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }







}
