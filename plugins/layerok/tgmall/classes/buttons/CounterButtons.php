<?php

namespace Layerok\TgMall\Classes\Buttons;

use Layerok\TgMall\Classes\Traits\Lang;
use Telegram\Bot\Keyboard\Keyboard;

class CounterButtons
{
    use Lang;
    public static function getButtons($initialCount, $handler): array
    {
        $minus = MinusButton::getButton(
            $handler ?? "noop",
            [
                'count' => $initialCount - 1
            ]
        );

        $count = TextButton::getButton($initialCount);

        $plus = PlusButton::getButton(
            $handler ?? "noop",
            [
                'count' => $initialCount + 1
            ]
        );

        return [$minus, $count, $plus];
    }
}
