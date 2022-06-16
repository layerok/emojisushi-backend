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

class ImportData extends Command {
    protected $name = 'poster:import';
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

        $this->output->newLine();
        $this->output->writeln('Creating categories...');
        $this->output->newLine();
        Artisan::call('poster:import-categories', ['--force' => true]);

        $this->output->newLine();
        $this->output->writeln('Creating spots and tablets...');
        $this->output->newLine();
        Artisan::call('poster:import-spots', ['--force' => true]);

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


        Product::truncate();
        ProductPrice::truncate();
        PropertyGroup::truncate();
        PropertyValue::truncate();
        Property::truncate();
        Variant::truncate();
        PaymentMethod::truncate();
        ShippingMethod::truncate();
        Currency::truncate();
        DB::table('offline_mall_property_property_group')->truncate();
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
            'name' => IngredientsGroup::SLUG_KEY,
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
        $method->name             = 'Готівкою';
        $method->payment_provider = 'offline';
        $method->sort_order       = 1;
        $method->code             = 'cash';
        $method->save();

        $this->output->progressAdvance();

        $method                   = new PaymentMethod();
        $method->name             = 'Картою';
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
        $method->name               = 'Самовивіз';
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
        $method->name               = "Кур'єр";
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
