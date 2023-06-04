<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Layerok\PosterPos\Classes\PosterTransition;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Classes\Index\Noop;
use OFFLINE\Mall\Classes\Index\ProductEntry;
use OFFLINE\Mall\Classes\Index\VariantEntry;
use OFFLINE\Mall\Models\ImageSet;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\Price;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\ShippingMethod;
use OFFLINE\Mall\Models\Variant;
use poster\src\PosterApi;
use Symfony\Component\Console\Input\InputOption;
use DB;

class ImportData extends Command {
    protected $name = 'poster:import';
    protected $description = 'Import Layerok.PosterPos data';
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
        $this->output->newLine();
        $this->output->writeln('Create uah currency...');
        $this->output->newLine();
        Artisan::call('poster:create-uah-currency');
        $this->output->newLine();
        $this->output->writeln('Create payment and delivery methods...');
        $this->output->newLine();
        Artisan::call('poster:create-payment-methods');
        Artisan::call('poster:create-shipping-methods');

        $this->output->newLine();
        $this->output->writeln('Importing products...');
        $this->output->newLine();
        Artisan::call('poster:import-products', ['--force' => true]);

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
        DB::table('offline_mall_prices')->truncate();
        ShippingMethod::truncate();
        PaymentMethod::truncate();

        Artisan::call('cache:clear');

        $index = app(Index::class);
        $index->drop(ProductEntry::INDEX);
        $index->drop(VariantEntry::INDEX);
    }




}
