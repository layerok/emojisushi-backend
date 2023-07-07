<?php

namespace Layerok\PosterPos\Models;

use Layerok\Telegram\Models\Bot;
use Layerok\Telegram\Models\Chat;
use Model;

class Settings extends Model
{
    public $implement = [\System\Behaviors\SettingsModel::class];

    // A unique code
    public $settingsCode = 'layerok_posterpos_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    public $belongsTo = [
        'chat' => Chat::class,
        'bot' => Bot::class,
    ];


}
