<?php namespace Layerok\TgMall\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateKeyboard extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:tg.mall.keyboard';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new `Layerok.TgMall` keyboard.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'Keyboard';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'keyboard/keyboard.stub'    => 'classes/keyboards/{{studly_name}}Keyboard.php',
    ];

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $name = $this->argument('keyboard');

        return [
            'name' => $name,
            'author' => $author,
            'plugin' => $plugin
        ];
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
            ['keyboard', InputArgument::REQUIRED, 'The name of the keyboard. Eg: StartMenu'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
