<?php namespace Layerok\Tgmall\Features\Category;

use Illuminate\Support\Collection;

use Layerok\TgMall\Classes\Keyboards\InlineKeyboard;
use Layerok\TgMall\Classes\Traits\CallbackData;
use Layerok\TgMall\Classes\Traits\Lang;
use OFFLINE\Mall\Classes\Utils\Money;
use OFFLINE\Mall\Models\Cart;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;

class CategoryProductKeyboard extends InlineKeyboard
{
    use Lang;
    use CallbackData;

    /** @var Cart */
    protected $cart;

    /** @var Product */
    protected $product;

    /** @var Money */
    protected $money;

    public function build(): void
    {
        $this->cart = $this->vars['cart'];
        $this->product = $this->vars['product'];

        $this->money = app(Money::class);

        if($this->product->isVariant()) {
            $variants = $this->getVariants($this->product);

            $variants->map(function($variant) {
                $this->makeButtonsRow($variant);
            });

        } else {
            $this->makeButtonsRow($this->product);
        }
    }


    public function getVariants(Product $product): Collection
    {
        return $product->variants()->published()->get();
    }

    public function makeButtonsRow($entry)
    {
        $arguments = [
            'qty' =>  1
        ];
        $totalPrice = $this->money->format(
            $entry->price()->price * 1,
            null,
            Currency::$defaultCurrency
        );

        $this->append([
            'text' => self::lang('buttons.price') . ": " . $totalPrice,
            'callback_data' => self::prepareCallbackData('noop')
        ]);

        if($entry instanceof Variant) {
            $product = $entry->product;
            $variant = $entry;
            $arguments['variant_id'] = $variant['id'];
            $this->append([
                'text' => $variant->getPropertiesDescriptionAttribute(),
                'callback_data' => self::prepareCallbackData('noop')
            ]);

        } else {
            $product = $entry;
            $variant = null;
            $arguments['product_id'] = $product['id'];
        }

        if ($this->cart->isInCart($product, $variant)) {
            $this->append([
                'text' => self::lang('buttons.added_to_cart'),
                'callback_data' => self::prepareCallbackData('noop')
            ]);
        } else {
           $this->append([
                'text' => self::lang('buttons.add_to_cart'),
                'callback_data' => self::prepareCallbackData(
                    'product_add',
                    $arguments
                )
            ]);
        }


        $this->nextRow();
    }


}
