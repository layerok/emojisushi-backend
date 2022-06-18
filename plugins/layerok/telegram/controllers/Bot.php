<?php namespace Layerok\Telegram\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Bot Backend Controller
 */
class Bot extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\ImportExportController::class
    ];

    public $importExportConfig = 'config_export_import.yaml';

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Layerok.Telegram', 'telegram', 'bot');
    }
}
