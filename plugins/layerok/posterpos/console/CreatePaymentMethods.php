<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use OFFLINE\Mall\Models\PaymentMethod;


class CreatePaymentMethods extends Command {
    protected $name = 'poster:create-payment-methods';
    protected $description = 'Create payment methods for mall';


    public function handle()
    {
        $this->output->newLine();
        $this->output->writeln('Creating payment methods...');
        $this->output->newLine();

        $cash = PaymentMethod::where('code', 'cash')->first();

        if($cash) {
            $this->output->success('Payment method with code [cash] exists, skipping creating it');
        } else {
            $this->output->writeln('Creating payment method with code [cash]');
            $method                   = new PaymentMethod();
            $method->name             = 'Готівкою';
            $method->payment_provider = 'offline';
            $method->sort_order       = 1;
            $method->code             = 'cash';
            $method->save();
        }

        $card = PaymentMethod::where('code', 'cash')->first();

        if($card) {
            $this->output->success('Payment method with code [card] exists, skipping to seed it');
        } else {
            $this->output->writeln('Creating payment method with code [cash]');
            $method                   = new PaymentMethod();
            $method->name             = 'Картою';
            $method->payment_provider = 'offline';
            $method->sort_order       = 1;
            $method->code             = 'card';
            $method->save();
        }

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
