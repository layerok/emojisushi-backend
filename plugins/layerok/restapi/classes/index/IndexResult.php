<?php

namespace Layerok\RestApi\Classes\Index;

class IndexResult
{
    /**
     * An array of all matching ids.
     *
     * @var array<integer>
     */
    public $ids = [];
    /**
     * @var int
     */
    public $totalCount = 0;

    public $ids_in_wishlist = [];

    public function __construct($ids, $totalCount, $ids_in_wishlist)
    {
        $this->ids        = $ids;
        $this->totalCount = $totalCount;
        $this->ids_in_wishlist = $ids_in_wishlist;
    }
}
