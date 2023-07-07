<?php

namespace Layerok\PosterPos\Models;

use Model;

class WayforpaySettings extends Model
{
    public $implement = [\System\Behaviors\SettingsModel::class];

    // A unique code
    public $settingsCode = 'layerok_posterpos_wayfopay_settings';
    public $settingsFields = '$\layerok/posterpos/models/settings/wayforpay_fields.yaml';



}
