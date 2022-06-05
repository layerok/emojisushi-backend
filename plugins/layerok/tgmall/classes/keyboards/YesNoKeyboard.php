<?php

namespace Layerok\TgMall\Classes\Keyboards;

use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;

class YesNoKeyboard extends InlineKeyboard
{

    use Lang;
    use CallbackData;

    public function build(): void
    {
        $this->append([
            'text' => 'Да',
            'callback_data' => self::prepareCallbackData($this->vars['yes']['handler'])
        ])->append([
            'text' => 'Нет',
            'callback_data' => self::prepareCallbackData($this->vars['no']['handler'])
        ]);
    }
}
