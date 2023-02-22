<?php namespace Cms\Classes;

use Cms\Models\PageLookupItem;

/**
 * PageLookup provides abstraction level for the page lookup operations.
 *
 * @method static PageLookup instance()
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class PageLookup
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * url is a helper that makes a URL for a page lookup item.
     */
    public static function url($address): string
    {
        return (string) (static::resolve($address)->url ?? '');
    }

    /**
     * resolve is a helper that makes a page lookup item from a schema.
     *
     * Supported options:
     * - nesting: Boolean value requesting nested items. Optional, false if omitted.
     */
    public static function resolve($address, array $options = []): ?PageLookupItem
    {
        if ($address instanceof PageLookupItem) {
            return $address;
        }

        return PageLookupItem::resolveFromSchema((string) $address, $options);
    }

    /**
     * processMarkup will replace links in content with resolved versions
     * For example: ="october://xxx" → ="https://..."
     */
    public static function processMarkup($markup): string
    {
        $searches = $replaces = [];
        if (preg_match_all('/="(october:\/\/.*?[^"])"/i', $markup, $matches)) {
            foreach ($matches[0] as $index => $search) {
                $ocUrl = $matches[1][$index] ?? null;
                if (!$ocUrl) {
                    continue;
                }

                $url = static::url($ocUrl);
                if (!$url) {
                    continue;
                }

                if (in_array($search, $searches)) {
                    continue;
                }

                $searches[] = $search;
                $replaces[] = str_replace($ocUrl, $url, $search);
            }
        }

        if ($searches) {
            $markup = str_replace($searches, $replaces, $markup);
        }

        return (string) $markup;
    }
}
