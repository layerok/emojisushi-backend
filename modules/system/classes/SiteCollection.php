<?php namespace System\Classes;

use Site;
use October\Rain\Database\Collection;

/**
 * SiteCollection is a collection of sites
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class SiteCollection extends Collection
{
    /**
     * isPrimary
     */
    public function isPrimary()
    {
        return $this->where('is_primary', true);
    }

    /**
     * isEnabled
     */
    public function isEnabled()
    {
        return $this->where('is_enabled', true);
    }

    /**
     * isEnabledEdit
     */
    public function isEnabledEdit()
    {
        return $this->where('is_enabled_edit', true);
    }

    /**
     * inGroup
     */
    public function inGroup($groupId = null)
    {
        if (!$groupId) {
            return $this;
        }

        return $this->where('group_id', $groupId);
    }

    /**
     * inLocale
     */
    public function inLocale($localeCode = null)
    {
        if (!$localeCode) {
            return new static;
        }

        return $this->filter(function($site) use ($localeCode) {
            return $site->matchesLocale($localeCode);
        });
    }

    /**
     * inSiteGroup
     */
    public function inSiteGroup($siteId = null)
    {
        $site = $siteId ? Site::getSiteFromId($siteId) : Site::getSiteFromContext();

        return $this->inGroup($site?->group_id);
    }

    /**
     * inSiteLocale
     */
    public function inSiteLocale($siteId = null)
    {
        $site = $siteId ? Site::getSiteFromId($siteId) : Site::getSiteFromContext();

        return $this->inLocale($site?->hard_locale);
    }
}
