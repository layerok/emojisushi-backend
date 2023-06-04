<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Layerok\PosterPos\Classes\ModificatorsGroup;
use Layerok\PosterPos\Classes\PosterTransition;
use Layerok\PosterPos\Models\HideCategory;
use Layerok\PosterPos\Models\HideProduct;
use Layerok\PosterPos\Models\Spot;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Index\Noop;
use OFFLINE\Mall\Classes\Index\ProductEntry;
use OFFLINE\Mall\Classes\Index\VariantEntry;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\ImageSet;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\Variant;
use poster\src\PosterApi;
use Symfony\Component\Console\Input\InputOption;
use DB;

class ImportProducts extends Command {
    protected $name = 'poster:import-products';
    protected $description = 'Fetch products from PosterPos api and import them into the database';

    public function handle()
    {
        $question = 'All existing OFFLINE.Mall products will be erased. Do you want to continue?';
        if ( ! $this->option('force') && ! $this->output->confirm($question, false)) {
            return 0;
        }

        // Use a Noop-Indexer so no unnecessary queries are run during seeding.
        // the index will be re-built once everything is done.
        $originalIndex = app(Index::class);
        app()->bind(Index::class, function () {
            return new Noop();
        });

        $rememberedProducts = Product::with([
            'hide_products_in_spot',
        ])->get();

        $rememberedCategories = Category::with([
            'hide_categories_in_spot',
        ])->get();


        $this->cleanup();

        Artisan::call('poster:create-uah-currency');
        Artisan::call('poster:import-spots', ['--force' => true]);
        Artisan::call('poster:import-ingredients', ['--force' => true]);
        Artisan::call('poster:import-categories', ['--force' => true]);
        $this->createProducts();

        $rememberedProducts->each(function($item) {
            $product = Product::where('poster_id', $item->poster_id)->first();
            if($product) {
                $product->description_short = $item->description_short;
                $product->published = $item->published;
                $product->save();
                $item->hide_products_in_spot->each(function($spot) use($product) {
                    $spot = Spot::where('poster_id', $spot->poster_id)->first();

                    if($spot) {
                        HideProduct::firstOrCreate([
                            'spot_id' => $spot->id,
                            'product_id' => $product->id,
                        ]);
                    }
            });
            }
        });

        $rememberedCategories->each(function($item) {
            $category = Category::where('poster_id', $item->category_id)->first();
            if($category) {
                $category->published = $item->published;
                $category->save();
                $item->hide_categories_in_spot->each(function($spot) use($category) {
                    $spot = Spot::where('poster_id', $spot->poster_id)->first();

                    if($spot) {
                        HideCategory::firstOrCreate([
                            'spot_id' => $spot->id,
                            'category_id' => $category->id,
                        ]);
                    }
                });
            }
        });

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
            ['reindex', null, InputOption::VALUE_NONE, 'Reindex after import.', null],
            ['type', null, InputOption::VALUE_OPTIONAL, 'Specify type of products.', null],
        ];
    }

    protected function cleanup()
    {
        $this->output->writeln('Removing data and files ...');
        Product::truncate();
        Variant::truncate();
        HideProduct::truncate();
        ProductPrice::truncate();

        DB::table('system_files')
            ->where('attachment_type', 'LIKE', 'OFFLINE%Mall%')
            ->orWhere('attachment_type', 'LIKE', 'mall.%')
            ->delete();
        ImageSet::truncate();

        //Artisan::call('cache:clear');

        if($this->option('reindex')) {
            $this->output->writeln('Dropping index...');
            $index = app(Index::class);
            $index->drop(ProductEntry::INDEX);
            $index->drop(VariantEntry::INDEX);
        }
    }

    protected function createProducts()
    {
        PropertyGroup::create([
           'name' => 'unknown_ingredient_group'
        ]);
        $this->output->newLine();
        $this->output->writeln('Creating products...');
        $this->output->newLine();

        $params = [];

        if($this->option('type')) {
            $params['type'] = $this->option('type');
            $this->output->writeln("Importing type [{$this->option('type')}]");
        }

        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];

        PosterApi::init($config);
        $products = (object)PosterApi::menu()->getProducts($params);
        $transition = new PosterTransition;
        $count = count($products->response);

        $this->output->progressStart($count);

        foreach ($products->response as $value) {
            $transition->createProduct($value);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }




}
