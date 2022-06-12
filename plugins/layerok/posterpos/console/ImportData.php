<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Layerok\PosterPos\Classes\PosterTransition;
use Layerok\PosterPos\Classes\RootCategory;
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

class ImportData extends Command {
    protected $name = 'posterpos:import';
    protected $description = 'Import Layerok.PosterPos data';
    public $ingredientsGroup = null;
    public $uaCurrency = null;

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
        $this->createCurrencies();
        $this->createIngredients();
        $this->createCategories();
        $this->createSpots();
        $this->createTablets();
        $this->createPaymentMethods();
        $this->createShippingMethods();
        $this->createProducts();

        app()->bind(Index::class, function () use ($originalIndex) {
            return $originalIndex;
        });

        Artisan::call('mall:reindex', ['--force' => true]);

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
        PaymentMethod::truncate();
        ShippingMethod::truncate();
        Currency::truncate();
        DB::table('offline_mall_property_property_group')->truncate();
        DB::table('offline_mall_category_property_group')->truncate();
        DB::table('offline_mall_prices')->truncate();

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

    protected function createIngredients() {
        $this->output->newLine();
        $this->output->writeln('Creating ingredients...');
        $this->output->newLine();

        $this->ingredientsGroup = PropertyGroup::create([
            'name' => 'ingredients',
            'display_name' => "Ингридиенты"
        ]);

        PosterApi::init();
        $records = (object)PosterApi::menu()->getIngredients();
        $count = count($records->response);

        $this->output->progressStart($count);

        foreach ($records->response as $value) {
            $category_id = $value->category_id;

            if($category_id === 8) {
                // ignore "Хозтовары" category
                continue;
            }
            $property = Property::create([
                'type' => 'checkbox',
                'poster_id' => $value->ingredient_id,
                'name' => $value->ingredient_name,

            ]);
            $this->ingredientsGroup->properties()->attach($property->id, [
                'filter_type' => 'set',
            ]);
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


        PosterApi::init();
        $categories = (object)PosterApi::menu()->getCategories();

        $root = Category::create([
            'name' => 'Меню',
            'slug'          => RootCategory::SLUG_KEY,
            'poster_id'     => null,
            'sort_order'    => 0,
        ]);

        $root->property_groups()->attach([$this->ingredientsGroup->id]);

        foreach ($categories->response as $category) {
            $poster_id = $category->category_id;
            $slug = $category->category_tag ?? str_slug($category->category_name);


            $category = Category::create([
                'name'          => (string)$category->category_name,
                'slug'          => $slug,
                'poster_id'     => (int)$poster_id,
                'sort_order'    => (int)$category->sort_order,
                'parent_id'     => $root->id,
            ]);

            // Привязываем к категории группу фильтров "Ингридиенты"
            $category->property_groups()->attach([$this->ingredientsGroup->id]);
        }
    }

    protected function createCurrencies()
    {
        $this->output->writeln('Creating currencies...');
        $this->uaCurrency = Currency::create([
            'code'     => 'UAH',
            'format'   => '{{ price|number_format(0, ".", ",") }} {{ currency.symbol }} ',
            'decimals' => 2,
            'is_default' => true,
            'symbol'   => '₴',
            'rate'     => 1,
        ]);
    }

    protected function createPaymentMethods() {
        $this->output->newLine();
        $this->output->writeln('Creating payment methods...');
        $this->output->newLine();


        $this->output->progressStart(2);

        $method                   = new PaymentMethod();
        $method->name             = 'Наличными';
        $method->payment_provider = 'offline';
        $method->sort_order       = 1;
        $method->code             = 'cash';
        $method->save();

        $this->output->progressAdvance();

        $method                   = new PaymentMethod();
        $method->name             = 'Картой';
        $method->payment_provider = 'offline';
        $method->sort_order       = 1;
        $method->code             = 'card';
        $method->save();

        $this->output->progressAdvance();


        $this->output->progressFinish();
    }

    protected function createShippingMethods() {
        $this->output->newLine();
        $this->output->writeln('Creating shipping methods...');
        $this->output->newLine();


        $this->output->progressStart(2);

        $method                     = new ShippingMethod();
        $method->name               = 'Самовывоз';
        $method->sort_order = 1;
        $method->save();

        (new Price([
            'price'          => 0,
            'currency_id'    => $this->uaCurrency->id,
            'priceable_type' => ShippingMethod::MORPH_KEY,
            'priceable_id'   => $method->id,
        ]))->save();


        $this->output->progressAdvance();

        $method                     = new ShippingMethod();
        $method->name               = 'Курьер';
        $method->sort_order = 1;
        $method->save();

        (new Price([
            'price'          => 0,
            'currency_id'    => $this->uaCurrency->id,
            'priceable_type' => ShippingMethod::MORPH_KEY,
            'priceable_id'   => $method->id,
        ]))->save();

        $this->output->progressAdvance();


        $this->output->progressFinish();
    }

}
