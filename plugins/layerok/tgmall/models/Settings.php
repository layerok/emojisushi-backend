<?php

namespace Layerok\TgMall\Models;

use Model;

class Settings extends Model
{
    public $implement = [\System\Behaviors\SettingsModel::class];

    // A unique code
    public $settingsCode = 'layerok_tgmall_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';
}
