<?php

namespace Layerok\BaseCode\Classes\Traits;

use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Models\User as TelegramUser;

trait HasSpot {
    public function hasSpot($chat_id): bool {
        $telegramUser = TelegramUser::where([
            'chat_id' => $chat_id
        ])->first();

        if(!$telegramUser) {
            return false;
        }

        $state = $telegramUser->state;

        if(!$state) {
            return false;
        }

        $spot_id = $state->getSpotId();
        $spot = Spot::where([
            'id' => $spot_id
        ])->first();

        if(!$spot) {
            return false;
        }

        return true;
    }
}
