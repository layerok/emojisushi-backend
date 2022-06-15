<?php

namespace Layerok\TgMall\Classes\Keyboards;

use Telegram\Bot\Keyboard\Keyboard;

abstract class InlineKeyboard implements InlineKeyboardInterface
{
    protected $keyboard;
    protected $vars;
    protected $rows = [[]];
    protected $rowIndex = 0;
    protected $listeners = [];

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
        $this->fire('beforeAppend', [$this, $params]);
        $this->rows[$this->rowIndex][] = $this->button($params);
        $this->fire('afterAppend', [$this, $params]);
        return $this;
    }

    public function listen($name, $handler) {
        $this->listeners[$name][] = $handler;
    }

    public function fire($name, $params = [], $halt = false) {
        if(!isset($this->listeners[$name])) {
            return null;
        }
        foreach($this->listeners[$name] as $listener) {
            $response = $listener($name, $params);
            if(!is_null($response) && $halt) {
                return $response;
            }
        }
    }

    public function getRowIndex() {
        return $this->rowIndex;
    }

    public function getColumnIndex() {
        return count($this->rows[$this->rowIndex]);
    }

}
