<?php
namespace Layerok\Basecode\Classes;

class Receipt
{
    public $emojis = [
        'document' => "\xF0\x9F\x93\x83 "
    ];

    protected $txt = "";

    protected $productNameResolver;
    protected $productCountResolver;
    protected $transResolver;

    public function __construct($txt = "")
    {
        $this->txt = $txt;
        $this->setProductCountResolver(function($product) {
            return $product['count'] ?? '';
        });
        $this->setProductNameResolver(function($product) {
            return $product['name'] ?? '';
        });

        $this->setTransResolver(function($key) {
            return $key;
        });
    }

    public function getProductName($product)
    {
        $resolver = $this->getProductNameResolver();
        return $resolver($product);
    }

    public function getProductCount($product)
    {
        $resolver = $this->getProductCountResolver();
        return $resolver($product);
    }

    public function getProductNameResolver(): \Closure
    {
        return $this->productNameResolver;
    }

    public function getProductCountResolver(): \Closure
    {
        return $this->productCountResolver;
    }

    public function setProductNameResolver($resolver) {
        $this->productNameResolver = $resolver;
    }

    public function setProductCountResolver($resolver) {
        $this->productCountResolver = $resolver;
    }

    public function setTransResolver($resolver) {
        $this->transResolver = $resolver;
    }

    public function getTransResolver(){
        return $this->transResolver;
    }

    public function trans($key): string
    {
        return $this->getTransResolver()($key);
    }

    public function products($products): Receipt
    {
        $this->b($this->trans('order_items'))
            ->colon()
            ->newLine();

        foreach ($products as $product) {
            $this->product(
                $this->getProductName($product),
                $this->getProductCount($product)
            )
                ->newLine();
        }

        return $this;
    }

    public function product($name, $count): Receipt
    {
        return $this->hyphen()
            ->space()
            ->p($name)
            ->space()
            ->p("x")
            ->p($count);
    }

    public function headline($headline): Receipt
    {
        return $this->emoji($this->emojis['document'])
            ->b($headline)
            ->newLine()
            ->newLine();
    }

    public function field($key, $txt): Receipt
    {
        if (!isset($txt)) {
            return $this;
        }
        if (empty($txt)) {
            return $this;
        }
        return $this->b($this->trans($key))
            ->colon()
            ->space()
            ->p($txt)
            ->newLine();
    }

    public function colon(): Receipt
    {
        return $this->p(":");
    }

    public function space(): Receipt
    {
        return $this->p(" ");
    }

    public function hyphen(): Receipt
    {
        return $this->p("-");
    }

    public function newLine(): Receipt
    {
        return $this->p("\n");
    }

    public function b($txt): Receipt
    {
        return $this->p("<b>" . $txt . "</b>");
    }

    public function emoji($emoji): Receipt
    {
        return $this->p($emoji);
    }

    public function p($txt): Receipt
    {
        if (isset($txt)) {
            $this->txt .= $txt;
        }
        return $this;
    }

    public function getText(): string
    {
        return $this->txt;
    }
}
