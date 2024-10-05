<?php namespace Tailor\Classes\Blueprint;

use Tailor\Classes\Blueprint;

/**
 * GlobalBlueprint
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
class GlobalBlueprint extends Blueprint
{
    /**
     * @var string typeName of the blueprint
     */
    protected $typeName = 'global';

    /**
     * makeBlueprintTableName where type can be used for content, join or repeater
     */
    protected function makeBlueprintTableName($type = 'content'): string
    {
        if ($type === 'content') {
            return 'tailor_globals';
        }

        if ($type === 'join') {
            return 'tailor_global_joins';
        }

        if ($type === 'repeater') {
            return 'tailor_global_repeaters';
        }

        return '';
    }

    /**
     * useMultisite
     */
    public function useMultisite(): bool
    {
        return (bool) $this->multisite;
    }

    /**
     * useMultisiteSync defaults to false.
     */
    public function useMultisiteSync(): bool
    {
        // Strict check since multisite can be set to true
        if (in_array($this->multisite, ['sync', 'locale', 'all', 'group'], true)) {
            return true;
        }

        if (!is_array($this->multisite)) {
            return false;
        }

        return (bool) array_get($this->multisite, 'sync', false);
    }
}
