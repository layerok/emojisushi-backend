<?php namespace Layerok\TgMall\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateCallbackHandler extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:tg.mall.handler';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new `Layerok.TgMall` callback handler.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'Handler';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'callback/handler.stub'    => 'classes/callbacks/{{studly_name}}Handler.php',
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

        $controller = $this->argument('handler');

        return [
            'name' => $controller,
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
            ['handler', InputArgument::REQUIRED, 'The name of the handler. Eg: ConfirmOrder'],
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
