<?php
namespace Layerok\BaseCode\Keyboards;
use Layerok\PosterPos\Models\Spot;
use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class SpotsKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    public function build(): void
    {
        Spot::where('published', '1')->get()->each(function($spot) {
            $this->append([
                'text' => $spot->name,
                'callback_data' => self::prepareCallbackData(
                    'change_spot',
                    [$spot->id]
                )
            ])->nextRow();
        });

    }
}
