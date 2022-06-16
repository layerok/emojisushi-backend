<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\PaymentMethod;
use OFFLINE\Mall\Models\Price;
use OFFLINE\Mall\Models\ShippingMethod;


class CreateShippingMethods extends Command {
    protected $name = 'poster:create-shipping-methods';
    protected $description = 'Create shipping methods for mall';


    public function handle()
    {
        $this->output->newLine();
        $this->output->writeln('Creating shipping methods...');
        $this->output->newLine();

        $uaCurrency = Currency::where('code', 'UAH')->first();
        if(!$uaCurrency) {
            \Artisan::call('poster:create-uah-currency');
            $uaCurrency = Currency::where('code', 'UAH')->first();
        }

        Price::truncate();
        ShippingMethod::truncate();

        $method                     = new ShippingMethod();
        $method->name               = 'Самовивіз';
        $method->sort_order = 1;
        $method->save();

        (new Price([
            'price'          => 0,
            'currency_id'    => $uaCurrency->id,
            'priceable_type' => ShippingMethod::MORPH_KEY,
            'priceable_id'   => $method->id,
        ]))->save();


        $method                     = new ShippingMethod();
        $method->name               = "Кур'єр";
        $method->sort_order = 1;
        $method->save();

        (new Price([
            'price'          => 0,
            'currency_id'    => $uaCurrency->id,
            'priceable_type' => ShippingMethod::MORPH_KEY,
            'priceable_id'   => $method->id,
        ]))->save();


        $this->output->success('All done!');
    }

    protected function getArguments()
    {
        return [];
    }

    protected function getOptions()
    {
        return [];
    }






}
