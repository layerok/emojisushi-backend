<?php

namespace Layerok\TgMall\Classes\Keyboards;

use Telegram\Bot\Keyboard\Keyboard;

abstract class InlineKeyboard implements InlineKeyboardInterface
{
    protected $keyboard;
    protected $vars;
    protected $rows = [];
    protected $rowIndex = 0;

    function __construct($vars = [])
    {
        $this->keyboard = new Keyboard();
        $this->keyboard->inline();
        $this->vars = $vars;
        $this->build();
        foreach($this->rows as $row) {
            $this->getKeyboard()->row(...$row);
        }
    }

    public function getKeyboard(): Keyboard
    {
        return $this->keyboard;
    }

    public function button($params)
    {
        return $this->keyboard::inlineButton($params);
    }

    public function nextRow(): self
    {
        $this->rowIndex++;
        $this->rows[$this->rowIndex] = [];
        return $this;
    }

    public function append($params): self
    {
        $this->rows[$this->rowIndex][] = $this->button($params);
        return $this;
    }

}
