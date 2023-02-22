<?php namespace Backend\Classes;

use Closure;
use October\Rain\Element\ElementBase;

/**
 * UiElement
 *
 * @method UiElement body(callable|array|string $body) body contents for the element, optional.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class UiElement extends ElementBase
{
    use \Backend\Traits\ElementRenderer;

    /**
     * __construct
     */
    public function __construct($config = [])
    {
        if (is_string($config) || $config instanceof Closure) {
            $this->body($config);
            $config = [];
        }

        parent::__construct($config);
    }
}
