<?php

namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Layerok\Restapi\Classes\Index\MySQL\MySQL;
use OFFLINE\Mall\Classes\CategoryFilter\QueryString;
use OFFLINE\Mall\Classes\CategoryFilter\SetFilter;
use OFFLINE\Mall\Classes\CategoryFilter\SortOrder\Bestseller;
use OFFLINE\Mall\Classes\CategoryFilter\SortOrder\SortOrder;
use OFFLINE\Mall\Classes\Index\Index;
use OFFLINE\Mall\Models\Category;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\PropertyGroup;
use OFFLINE\Mall\Models\Variant;
use OFFLINE\Mall\Models\Wishlist;
use Session;

class ProductController extends Controller
{
    public $categories;
    public $category;
    public $filter;
    public $includeChildren;
    public $perPage = 25;
    public $pageNumber = 1;
    public $includeVariants = false;
    public $offset;
    public $limit;
    public $totalCount;
    public $wishlist_only;

    public function fetch(): JsonResponse
    {
        $this->offset = input('offset');
        $this->limit = input('limit');
        $this->wishlist_only = input('wishlist');
        $this->includeChildren = input('include_children');;
        $this->category = $this->getCategory();
        $this->filter = input('filter');

        $this->perPage = $this->limit;

        if ($this->category) {
            $this->categories = collect([$this->category]);
            if ($this->includeChildren) {
                $this->categories = $this->category->getAllChildrenAndSelf();
            }
        }

        $items = $this->getItems();

        $group = PropertyGroup::where('id', 1)->first(); // 1 - id группы "Ингридиенты"
        $filters = $group->properties()->get();


        return response()->json([
            'data' => $items->toArray(),
            'meta' => [
                'total' => $this->totalCount,
                'offset' => $this->offset,
                'limit' => $this->limit,
                'sort_options' => $this->getSortOptions(),
                'filters' => $filters
            ]
        ]);
    }

    public function getSortOptions() {
        return collect(SortOrder::options(true))->keys();
    }

    protected function getItems()
    {
        $filters   = $this->getFilters();
        $sortOrder = $this->getSortOrder();

        $model    = $this->includeVariants ? new Variant() : new Product();
        $useIndex = $this->includeVariants ? 'variants' : 'products';

        $sortOrder->setFilters(clone $filters);

        $index  = new MySQL();
        $result = $index->fetch(
            $useIndex,
            $filters,
            $sortOrder,
            $this->perPage,
            $this->pageNumber,
            $this->wishlist_only
        );

        $ids_in_wishlist = $result->ids_in_wishlist;


        $this->totalCount = $result->totalCount;
        // Every id that is not an int is a "ghosted" variant, with an id like
        // product-1. These ids have to be fetched separately. This enables us to
        // query variants and products that don't have any variants from the same index.
        $itemIds  = array_filter($result->ids, 'is_int');
        $ghostIds = array_diff($result->ids, $itemIds);

        $models = $model->with($this->productIncludes())->find($itemIds);
        $ghosts = $this->getGhosts($ghostIds);

        // Preload all pricing information for related products. This is used in case a Variant
        // is inheriting it's parent product's pricing information.
        if ($model instanceof Variant) {
            $models->load(['product.customer_group_prices', 'product.prices', 'product.additional_prices']);
        }

        // Insert the Ghost models back at their old position so the sort order remains.
        $items = collect($result->ids)->map(function ($id) use ($models, $ghosts) {
            return is_int($id)
                ? $models->find($id)
                : $ghosts->find(str_replace('product-', '', $id));
        });

        $items->each(function($item, $i) use ($ids_in_wishlist, $items) {
            $index = $this->includeVariants ? 'variant': 'product';
            if(in_array($item["{$index}_id"], $ids_in_wishlist[$index])) {
                $items[$i]->is_favorite_ = true;
            }

        });

        return $items;

    }

    protected function getCategory()
    {
        if ($this->category) {
            return $this->category;
        }

        $slug = input('category_slug');

        try {
            return Category::where('slug', $slug)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getFilters(): Collection
    {
        $filter = request()->all();
        if ($this->filter) {
            parse_str($this->filter, $filter);
        }

        $filter = array_wrap($filter);

        $filters = (new QueryString())->deserialize($filter, $this->category);
        if ($this->categories && !isset($filters['category_id'])) {
            $filters->put('category_id', new SetFilter('category_id', $this->categories->pluck('id')->toArray()));
        }

        return $filters;
    }

    protected function getSortOrder(): SortOrder
    {
        $key = input('sort', null ?? SortOrder::default());

        return SortOrder::fromKey($key);
    }

    protected function productIncludes(): array
    {
        return [
            'image_sets',
            'prices',
            'additional_prices',
            'property_values'
            ];
    }

    protected function getGhosts(array $ids)
    {
        if (count($ids) < 1) {
            return collect([]);
        }

        $ids = array_map(function ($id) {
            return (int)str_replace('product-', '', $id);
        }, $ids);

        return Product::with($this->productIncludes())->find($ids);
    }

}
