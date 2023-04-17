<?php

namespace Layerok\Restapi\Classes\Sort;

use \OFFLINE\Mall\Classes\CategoryFilter\SortOrder\SortOrder;

class Category extends SortOrder
{
    public function key(): string
    {
        return 'category';
    }

    public function property(): string
    {
        return 'category_id';
    }

    public function direction(): string
    {
        return 'desc';
    }
}
