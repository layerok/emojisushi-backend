<?php namespace Layerok\Tgmall\Features\Cart;


use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Traits\Warn;
use OFFLINE\Mall\Models\CartProduct;
use OFFLINE\Mall\Models\Product;
use Telegram\Bot\Keyboard\Keyboard;

class CartHandler extends Handler
{
    use Warn;
    use Lang;

    protected $name = "cart";

    public $product;

    public $types = ['remove', 'update', 'list'];

    public function validate(): bool
    {
        $rules = [
            'type' => [
                'required',
                Rule::in($this->types),
            ],
            'id' => 'exists:offline_mall_cart_products,id',
            'qty' => 'integer'
        ];

        $validation = Validator::make($this->arguments, $rules);

        if ($validation->fails()) {
            $this->errors =
                array_merge(
                    $validation->errors()->get('type'),
                    $validation->errors()->get('id'),
                    $validation->errors()->get('qty')
                );
            return false;
        }

        return true;
    }

    public function run()
    {

        $type = $this->arguments['type'];

        switch ($type) {
            case "list":
                $this->showCart();
                break;
            case "update":
                $this->updateProduct();
                break;
            case "remove":
                $this->product = Product::find($this->arguments['id']);
                $this->removeProduct();
                break;
        }
    }



    public function updateProduct()
    {
        $cart = $this->getCart();

        $cartProduct = $cart
            ->products()
            ->where('id', '=', $this->arguments['id'])
            ->first();

        if (isset($cartProduct) && ($cartProduct->quantity + $this->arguments['qty']) < 1) {
            return;
        }

        $cart->addProduct(
            $cartProduct->product,
            $this->arguments['qty'],
            $cartProduct->variant
        );
        $cart->refresh();
        $cartProduct->refresh();

        $this->editCartProductMessage($cartProduct);

        $this->editCartFooterMessage();

    }

    public function removeProduct()
    {
        $this->deleteMessage(
            $this->getTriggerMessageId()
        );

        $cart = $this->getCart();

        $cartProduct = $cart
            ->products()
            ->where('id', '=', $this->arguments['id'])
            ->first();

        if(!isset($cartProduct)) {
            return;
        }

        $cart->removeProduct($cartProduct);
        $cart->refresh();

        $this->editCartFooterMessage();
    }


    public function showCart()
    {
        $this->replyWithMessage([
            'text' => self::lang('buttons.cart')
        ]);

        $this->listCartProducts();

        $response = $this->replyWithMessage(
            $this->cartFooterMessage()
        );

        if ($this->getCart()->products->count() === 0) {
            return;
        }

        $msg_id = $response["message_id"];

        $this->getState()->setCartTotalMsg(
            [
                'id' => $msg_id,
                'total' => $this->getCart()->totals()->totalPostTaxes()
            ]
        );
    }

    public function listCartProducts()
    {
        $this->getCart()->products->map(function ($cartProduct) {
            $this->sendCartProduct($cartProduct);
        });
    }

    public function sendCartProduct($cartProduct)
    {

        $caption = $cartProduct->product->getCaptionForTelegram();

        $markup = new CartProductKeyboard([
            'cartProduct' => $cartProduct,
        ]);

        $keyboard = $markup->getKeyboard();

        if (is_null($cartProduct->product->image)) {
            //  У товара нет изображения
            $this->replyWithMessage([
                'text' => $caption,
                'reply_markup' => $keyboard,
                'parse_mode' => 'html',
            ]);
            return;
        }

        $image = $cartProduct->product->image;

        $response = $this->replyWithPhoto([
            'photo' => $image->getTelegramFileId(),
            'caption' => $caption,
            'reply_markup' => $keyboard,
            'parse_mode' => 'html',
        ]);

        $image->setTelegramFileId($response);
    }

    public function editCartProductMessage(CartProduct $cartProduct)
    {
        $markup = new CartProductKeyboard([
            'cartProduct' => $cartProduct,
        ]);

        $keyboard = $markup->getKeyboard();

        $this->editMessageReplyMarkup(
            $this->getTriggerMessageId(),
            [
                'reply_markup' => $keyboard
            ]
        );
    }

    public function editCartFooterMessage()
    {
        $cartTotalMsg = $this->getState()->getCartTotalMsg();

        if (!isset($cartTotalMsg)) {
            return;
        }

        $cartTotal = $this->getCart()->totals()->totalPostTaxes();

        if ($cartTotalMsg['total'] == $cartTotal) {
            // Общая стоимость товаров в корзине совпадает с тем что написано в сообщении
            return;
        }
        $this->editMessageReplyMarkup($cartTotalMsg['id'], $this->cartFooterMessage());
        $this->getState()->setCartTotalMsgTotal($cartTotal);
    }


    public function cartFooterMessage(): array
    {
        $text = $this->getCart()->products->count() === 0 ?
            self::lang('texts.cart_is_empty') :
            self::lang('texts.cart');
        return [
            'text' => $text,
            'reply_markup' => $this->cartFooterKeyboard()
        ];
    }

    public function cartFooterKeyboard(): Keyboard
    {
        if ($this->getCart()->products->count() === 0) {
            $markup = new CartEmptyKeyboard();
        } else {
            $markup = new CartFooterKeyboard([
                'cart' => $this->getCart()
            ]);
        }

        return $markup->getKeyboard();
    }


}
