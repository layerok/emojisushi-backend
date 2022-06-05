<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Layerok\PosterPos\Classes\PosterTransition;
use Layerok\PosterPos\Models\HideCategory;
use Layerok\PosterPos\Models\HideProduct;
use Layerok\PosterPos\Models\Spot;
use Layerok\PosterPos\Models\Tablet;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Index\Noop;
use OFFLINE\Mall\Classes\Index\ProductEntry;
use OFFLINE\Mall\Classes\Index\VariantEntry;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\ImageSet;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\Property;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\PropertyValue;
use OFFLINE\Mall\Models\Variant;
use poster\src\PosterApi;
use Symfony\Component\Console\Input\InputOption;
use DB;
use System\Models\File;

class ImportData extends Command {
    protected $name = 'posterpos:import';
    protected $description = 'Import Layerok.PosterPos data';

    public function handle()
    {
        $question = 'All existing OFFLINE.Mall data will be erased. Do you want to continue?';
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
        $this->createSpots();
        $this->createTablets();
        $this->createCurrencies();
        $this->createCategories();

        $this->createProducts();


        app()->bind(Index::class, function () use ($originalIndex) {
            return $originalIndex;
        });

        Artisan::call('mall:reindex', ['--force' => true]);

        $this->output->success('All done!');
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Don\'t ask before deleting the data.', null],
        ];
    }

    protected function cleanup()
    {
        $this->output->writeln('Resetting plugin data...');

        Category::truncate();
        Product::truncate();
        ProductPrice::truncate();
        PropertyGroup::truncate();
        PropertyValue::truncate();
        Property::truncate();
        Variant::truncate();
        Spot::truncate();
        HideCategory::truncate();
        HideProduct::truncate();
        Spot::truncate();
        Tablet::truncate();


        Artisan::call('cache:clear');

        DB::table('system_files')
            ->where('attachment_type', 'LIKE', 'OFFLINE%Mall%')
            ->orWhere('attachment_type', 'LIKE', 'mall.%')
            ->delete();
        ImageSet::truncate();



        $index = app(Index::class);
        $index->drop(ProductEntry::INDEX);
        $index->drop(VariantEntry::INDEX);
    }

    protected function createProducts()
    {
        $this->output->newLine();
        $this->output->writeln('Creating products...');
        $this->output->newLine();

        PosterApi::init();
        $products = (object)PosterApi::menu()->getProducts();
        $transition = new PosterTransition;
        $count = count($products->response);


        $this->output->progressStart($count);

        foreach ($products->response as $value) {
            $transition->createProduct($value);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }

    protected function createSpots() {
        $this->output->newLine();
        $this->output->writeln('Creating spots...');
        $this->output->newLine();

        PosterApi::init();

        $records = (object)PosterApi::access()->getSpots();

        $count = count($records->response);

        $this->output->progressStart($count);

        foreach ($records->response as $record) {
            Spot::create([
                'address' => $record->spot_adress,
                'name' => $record->spot_name,
            ]);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }

    protected function createTablets() {
        $this->output->newLine();
        $this->output->writeln('Creating tablets...');
        $this->output->newLine();

        PosterApi::init();

        $records = (object)PosterApi::access()->getTablets();

        $count = count($records->response);

        $this->output->progressStart($count);

        foreach ($records->response as $record) {
            Tablet::create([
                'name' => $record->tablet_name,
                'spot_id' => $record->spot_id,
                'tablet_id' => $record->tablet_id
            ]);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }

    protected function createCategories()
    {
        $this->output->writeln('Creating categories...');
        DB::table('offline_mall_categories')->truncate();
        DB::table('offline_mall_category_property_group')->truncate();

        PosterApi::init();
        $categories = (object)PosterApi::menu()->getCategories();

        foreach ($categories->response as $category) {
            $poster_id = $category->category_id;
            $slug = $category->category_tag ?? str_slug($category->category_name);


            Category::create([
                'name'          => (string)$category->category_name,
                'slug'          => $slug,
                'poster_id'     => (int)$poster_id,
                'sort_order'    => (int)$category->sort_order
            ]);
        }
    }

    protected function createCurrencies()
    {
        $this->output->writeln('Creating currencies...');
        DB::table('offline_mall_currencies')->truncate();
        Currency::create([
            'code'     => 'UAH',
            'format'   => '{{ currency.symbol }} {{ price|number_format(2, ".", ",") }}',
            'decimals' => 2,
            'is_default' => true,
            'symbol'   => '₴',
            'rate'     => 1,
        ]);
    }

}
