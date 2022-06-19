<?php

namespace Layerok\TgMall\Features\Checkout\Messages;

use Illuminate\Support\Facades\Validator;
use Layerok\TgMall\Classes\Callbacks\CallbackQueryBus;
use Layerok\TgMall\Classes\Messages\AbstractMessageHandler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Features\Checkout\Keyboards\IsRightPhoneKeyboard;

class OrderNameHandler extends AbstractMessageHandler
{
    use Lang;

    protected $errors;

    public function validate(): bool
    {
        $data = [
            'firstname' => $this->text
        ];

        $rules = [
            'firstname' => 'required|min:2',
        ];

        $messages = [
            'firstname.required' => "Имя обязательно для заполнения",
            'firstname.min' => "Имя должно содержать минимум :min символа"
        ];

        $validation = Validator::make($data, $rules, $messages);

        if ($validation->fails()) {
            $this->errors = $validation->errors()->get('firstname');
            return false;
        }
        return true;
    }

    public function handle()
    {
        $this->getCustomer()->firstname = $this->text;
        $this->getCustomer()->save();

        if (isset($this->getTelegramUser()->phone)) {

            $k = new IsRightPhoneKeyboard();
            $this->sendMessage([
                'text' => self::lang('texts.right_phone_number') . ' ' . $this->getTelegramUser()->phone . '?',
                'reply_markup' => $k->getKeyboard(),
            ]);

            return;
        }

        CallbackQueryBus::instance()->make('enter_phone', []);

    }

    public function handleErrors(): void
    {
        foreach ($this->errors as $error) {
            $this->sendMessage([
                'text' => $error . '. Попробуйте снова.',
            ]);
        }
    }
}
