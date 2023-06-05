<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Layerok\PosterPos\Models\HideCategory;
use Layerok\PosterPos\Models\HideProduct;
use Layerok\PosterPos\Models\Spot;

use Layerok\Telegram\Models\Bot;
use Layerok\Telegram\Models\Chat;
use OFFLINE\Mall\Models\Address;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\User;
use poster\src\PosterApi;
use RainLab\Location\Models\Country;
use Symfony\Component\Console\Input\InputOption;
use DB;

class ImportSpots extends Command {
    protected $name = 'poster:import-spots';
    protected $description = 'Fetch spots from PosterPos api and import into database';


    public function handle()
    {
        $question = 'All spots from Layerok.PosterPos will be erased. Do you want to continue?';
        if ( ! $this->option('force') && ! $this->output->confirm($question, false)) {
            return 0;
        }

        // cleanup
        Spot::truncate();
        HideCategory::truncate();
        HideProduct::truncate();

        $this->output->newLine();
        $this->output->writeln('Creating spots...');
        $this->output->newLine();
        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];

        PosterApi::init($config);

        $records = (object)PosterApi::access()->getSpots();

        $count = count($records->response);

        $this->output->progressStart($count);

        foreach ($records->response as $record) {
            $bot = Bot::where('id', 1)->first();
            $chat = Chat::where('id', 1)->first();

            $bot_id = $bot ? $bot->id : null;
            $chat_id = $chat ? $chat->id : null;
            $pass = "qweasdqweaasd";

            $user = User::create([
                'name' => '!!',
                'surname' => $record->spot_name,
                'email' => str_slug($record->spot_name) . "@email.com",
                'username' => str_slug($record->spot_name),
                'password' => $pass,
                'password_confirmation' => $pass
            ]);

            $customer = new Customer();
            $customer->firstname = $user->name;
            $customer->lastname = $user->surname;
            $customer->user_id = $user->id;
            $customer->save();

            $address = new Address();

            $address->name = $record->spot_name;
            $address->lines = $record->spot_adress || $record->spot_name;
            $address->customer_id = $customer->id;
            $address->zip = '65125';
            $address->city = 'Одеса';
            $address->country_id = Country::where('code', 'UA')->first()->id;
            $address->save();

            $address->save();

            Spot::create([
                'address_id' => $address->id,
                'name' => $record->spot_name,
                'bot_id' => $bot_id,
                'chat_id' => $chat_id,
                'phones' => '+38 (093) 366 28 69, +38 (068) 303 45 51',
                'poster_id' => $record->spot_id
            ]);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        \Artisan::call('poster:import-tablets', ['--force' => true]);

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
