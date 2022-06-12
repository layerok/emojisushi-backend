<?php
namespace Layerok\Telegram\Console;

use Illuminate\Console\Command;
use Layerok\Telegram\Models\Bot;
use Layerok\Telegram\Models\Chat;

use Symfony\Component\Console\Input\InputOption;

class ImportData extends Command {
    protected $name = 'telegram:seed-test';
    protected $description = 'Seed Layerok.Telegram test data';


    public function handle()
    {
        $question = 'All existing Layerok.Telegram data will be erased. Do you want to continue?';
        if ( ! $this->option('force') && ! $this->output->confirm($question, false)) {
            return 0;
        }


        $this->cleanup();
        $this->createTelegramBots();
        $this->createTelegramChats();


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

        Bot::truncate();
        Chat::truncate();

    }


    protected function createTelegramBots() {
        $this->output->newLine();
        $this->output->writeln('Creating telegram bots...');
        $this->output->newLine();



        $this->output->progressStart(1);


        Bot::create([
            'token' => '5009009410:AAF1mz_UhdBxkgQ4GxTO7G2XVqxBwv-XYTU',
            'name' => "Test bot",
        ]);
        $this->output->progressAdvance();


        $this->output->progressFinish();
    }

    protected function createTelegramChats() {

        $this->output->newLine();
        $this->output->writeln('Creating telegram bots...');
        $this->output->newLine();



        $this->output->progressStart(1);


        Chat::create([
            'internal_id' => '-587888839',
            'name' => "Test chat",
        ]);
        $this->output->progressAdvance();


        $this->output->progressFinish();
    }


}
