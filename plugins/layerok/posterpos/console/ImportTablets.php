<?php
namespace Layerok\PosterPos\Console;

use Illuminate\Console\Command;
use Layerok\PosterPos\Models\Spot;
use Layerok\PosterPos\Models\Tablet;
use poster\src\PosterApi;
use Symfony\Component\Console\Input\InputOption;
use DB;

class ImportTablets extends Command {
    protected $name = 'poster:import-tablets';
    protected $description = 'Fetch tablets from PosterPos api and import into database';


    public function handle()
    {
        $question = 'All tablets from Layerok.PosterPos will be erased. Do you want to continue?';
        if ( ! $this->option('force') && ! $this->output->confirm($question, false)) {
            return 0;
        }

        // cleanup
        Tablet::truncate();

        $this->output->newLine();
        $this->output->writeln('Creating tablets...');
        $this->output->newLine();

        $config = [
            'access_token' => config('poster.access_token'),
            'application_secret' => config('poster.application_secret'),
            'application_id' => config('poster.application_id'),
            'account_name' => config('poster.account_name')
        ];

        PosterApi::init($config);

        $records = (object)PosterApi::access()->getTablets();

        $count = count($records->response);

        $this->output->progressStart($count);

        foreach ($records->response as $record) {
            $spot = Spot::where('poster_id', $record->spot_id)->first();
            $spot_id = $spot ? $spot->id: null;
            Tablet::create([
                'name' => $record->tablet_name,
                'spot_id' => $spot_id,
                'tablet_id' => $record->tablet_id
            ]);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

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
