<?php

namespace Layerok\Tgmall\Features\Product;

use Illuminate\Support\Facades\Validator;
use Layerok\TgMall\Classes\Callbacks\Handler;
use Layerok\TgMall\Classes\Traits\Lang;
use Layerok\TgMall\Classes\Traits\Warn;
use Layerok\Tgmall\Features\Category\CategoryFooterKeyboard;
use Layerok\Tgmall\Features\Category\CategoryProductKeyboard;
use OFFLINE\Mall\Classes\Exceptions\OutOfStockException;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;

class AddProductHandler extends Handler
{
    use Lang;
    use Warn;

    protected $name = "product_add";

    /** @var Product */
    protected $product;

    /** @var Variant */
    protected $variant;

    public function validate(): bool
    {
        $rules = [
            'product_id' => 'required_without:variant_id|exists:offline_mall_products,id',
            'variant_id' => 'required_without:product_id|exists:offline_mall_product_variants,id',
            'qty' => 'required|integer|min:1'
        ];

        $validation = Validator::make($this->arguments, $rules);

        if ($validation->fails()) {
            $this->errors =
                array_merge(
                    $validation->errors()->get('product_id'),
                    $validation->errors()->get('variant_id'),
                    $validation->errors()->get('qty')
                );
            return false;
        }

        return true;

    }

    public function run()
    {

        if(isset($this->arguments['product_id'])) {
            $this->variant = null;
            $this->product = Product::find($this->arguments['product_id']);
        } else if(isset($this->arguments['variant_id'])) {
            $this->variant = Variant::find($this->arguments['variant_id']);
            $this->product = $this->variant->product;
        }

        try {
            $this->getCart()->addProduct(
                $this->product,
                $this->arguments['qty'],
                $this->variant
            );
        } catch (OutOfStockException $e) {
            $this->replyWithMessage([
                'chat_id' => $this->getChatId(),
                'text' => $e->getMessage()
            ]);
            return;
        }

        $this->getCart()->refresh();

        $cartCountMsg = $this->getState()->getCartCountMsg();

        $markup = new CategoryProductKeyboard([
            'cart' => $this->getCart(),
            'product' => $this->product,
        ]);

        if (!$cartCountMsg) {
            return;
        }

        $this->editMessageReplyMarkup(
            $this->getTriggerMessageId(),
            [
                'reply_markup' => $markup->getKeyboard()
            ]
        );

        if ($cartCountMsg['count'] == $this->getCart()->products->count()) {
            // Кол-во товаров в корзине совпадает с тем, что написано в сообщении
            return;
        }

        $markup = new CategoryFooterKeyboard([
            'cart' => $this->getCart(),
            'category_id' =>$cartCountMsg['category_id'],
            'page' => $cartCountMsg['page']
        ]);

        $this->editMessageReplyMarkup($cartCountMsg['id'], [
            'reply_markup' => $markup->getKeyboard()
        ]);


    }

}
