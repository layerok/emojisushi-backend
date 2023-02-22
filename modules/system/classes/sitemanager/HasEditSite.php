<?php namespace System\Classes\SiteManager;

use Cms;
use Event;
use Config;
use System\Models\SiteDefinition;

/**
 * HasEditSite
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasEditSite
{
    /**
     * getEditSite returns the edit theme
     */
    public function getEditSite()
    {
        return $this->getSiteFromId($this->getEditSiteId())
            ?: $this->getPrimarySite();
    }

    /**
     * getEditSiteId
     */
    public function getEditSiteId()
    {
        return Config::get('system.edit_site');
    }

    /**
     * setEditSite
     */
    public function setEditSite($site)
    {
        $this->setEditSiteId($site->id);
    }

    /**
     * setEditSiteId
     */
    public function setEditSiteId($id)
    {
        Config::set('system.edit_site', $id);

        /**
         * @event system.site.setEditSite
         * Fires when the edit site has been changed.
         *
         * Example usage:
         *
         *     Event::listen('system.site.setEditSite', function($id) {
         *         \Log::info("Site has been changed to $id");
         *     });
         *
         */
        Event::fire('system.site.setEditSite', compact('id'));
    }

    /**
     * hasAnyEditSite returns true if there are edit sites
     */
    public function hasAnyEditSite(): bool
    {
        return $this->listSites()->where('is_enabled_edit', true)->count() > 0;
    }

    /**
     * hasMultiEditSite returns true if there are multiple sites for editing
     */
    public function hasMultiEditSite(): bool
    {
        return $this->listSites()->where('is_enabled_edit', true)->count() > 1;
    }

    /**
     * listEditEnabled
     */
    public function listEditEnabled()
    {
        return $this->listSites()->where('is_enabled_edit', true);
    }

    /**
     * applyEditSite applies edit site configuration values to the application,
     * typically used for backend requests.
     */
    public function applyEditSite(SiteDefinition $site)
    {
        if ($site->theme) {
            Config::set('cms.edit_theme', $site->theme);
        }

        if ($site->is_prefixed) {
            Cms::setUrlPrefix($site->route_prefix);
        }
    }
}
