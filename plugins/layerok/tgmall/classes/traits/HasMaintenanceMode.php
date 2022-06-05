<?php namespace Layerok\TgMall\Classes\Traits;

use Layerok\TgMall\Models\Settings;
use Layerok\TgMall\Models\User as TelegramUser;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

trait HasMaintenanceMode
{
    public function checkMaintenanceMode(Api $api, Update $update, TelegramUser $telegramUser): bool
    {
        $is_maintenance = $this->isMaintenanceMode($update);

        if($is_maintenance) {
            $api->sendMessage([
                'text' =>  'Приносим наши извинения. Над ботом временно ведутся технические работы.' .
                    ' Пока Вы можете воспользоваться нашим сайтом https://emojisushi.com.ua',
                'chat_id' => $telegramUser->id
            ]);
        }
        return $is_maintenance;
    }

    public function isMaintenanceMode(Update $update): bool
    {
        $chat = $update->getChat();
        if (Settings::get('is_maintenance_mode', env('TG_MALL_IS_MAINTENANCE_MODE', false))) {
            if (Settings::get('developer_chat_id', env('TG_MALL_DEVELOPER_CHAT_ID', false)) == $chat->id
                && Settings::get('pass_developer', env('TG_MALL_PASS_DEVELOPER', false))) {
                // если мы хотим дебажить как админы
                return false;
            } else {
                return true;
            }
        }
        return false;
    }


}
