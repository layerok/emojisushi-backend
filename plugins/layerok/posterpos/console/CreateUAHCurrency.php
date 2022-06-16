<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use OFFLINE\Mall\Models\Currency;


class CreateUAHCurrency extends Command {
    protected $name = 'poster:create-uah-currency';
    protected $description = 'For now just create currency for ukrainian hryvna';


    public function handle()
    {
        $this->output->newLine();
        $this->output->writeln('Creating ukrainian hryvna currency...');
        $this->output->newLine();

        $uaCurrency = Currency::where('code', 'UAH')->first();

        if($uaCurrency) {
            $this->output->success('Ukrainian currency exists!');
            return;
        }

        Currency::create([
            'code'     => 'UAH',
            'format'   => '{{ price|number_format(0, ".", ",") }} {{ currency.symbol }} ',
            'decimals' => 2,
            'is_default' => true,
            'symbol'   => '₴',
            'rate'     => 1,
        ]);



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
