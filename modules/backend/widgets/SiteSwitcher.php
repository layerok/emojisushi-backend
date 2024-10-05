<?php namespace Backend\Widgets;

use Site;
use Event;
use Block;
use Cookie;
use Request;
use BackendAuth;
use Cms\Classes\Theme;
use Backend\Classes\WidgetBase;
use Backend\Models\UserPreference;

/**
 * SiteSwitcher widget.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class SiteSwitcher extends WidgetBase
{
    /**
     * @var string defaultAlias to identify this widget.
     */
    protected $defaultAlias = 'siteSwitcher';

    /**
     * @var string|null switchHandler is set by setSwitchHandler.
     */
    protected $switchHandler;

    /**
     * bindToController
     */
    public function bindToController()
    {
        parent::bindToController();

        if ($id = get('_site_id')) {
            $this->applyEditSiteFromRequest($id);
        }
        elseif ($id = Request::header('X_SITE_ID')) {
            $this->applyEditSiteFromHeader($id);
        }
        else {
            $this->applyEditSiteFromPreference();
        }

        // Disable banner for now
        // $this->applyEditSiteBanner();
    }

    /**
     * @inheritDoc
     */
    public function render($extraVars = [])
    {
        $this->prepareVars($extraVars);
        return $this->makePartial('siteswitcher');
    }

    /**
     * @inheritDoc
     */
    public function renderSubmenu()
    {
        $this->prepareVars();
        return $this->makePartial('submenu');
    }

    /**
     * prepareVars for display
     */
    public function prepareVars($extraVars = [])
    {
        foreach ($extraVars as $key => $val) {
            $this->vars[$key] = $val;
        }

        $useMultisite = Site::hasMultiEditSite();

        $this->vars['switchHandler'] = $this->switchHandler;
        $this->vars['useMultisite'] = $useMultisite;
        $this->vars['canManageSite'] = BackendAuth::userHasAccess('settings.manage_sites');
        $this->vars['useAnySite'] = Site::hasAnySite();
        $this->vars['editSite'] = Site::getEditSite() ?: Site::getAnySite();
        $this->vars['sites'] = Site::listEditEnabled();
    }

    /**
     * setSwitchHandler enables the use of an AJAX handler when switching the site
     */
    public function setSwitchHandler(string $name = null)
    {
        $this->switchHandler = $name;
    }

    /**
     * loadAssets adds widget specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * @return void
     */
    protected function loadAssets()
    {
        $this->addCssBundle('css/siteswitcher.css', 'global');
        $this->addJsBundle('js/siteswitcher.js', 'global');
    }

    /**
     * applyEditSiteFromRequest
     */
    protected function applyEditSiteFromRequest($id)
    {
        if (!Request::ajax()) {
            Theme::resetEditTheme();
        }

        $this->storeBackendPreference($id);

        Site::applyEditSiteId($id);

        Site::resetCache();
    }

    /**
     * applyEditSiteFromHeader
     */
    protected function applyEditSiteFromHeader($id)
    {
        Site::applyEditSiteId($id);
    }

    /**
     * applyEditSiteFromPreference
     */
    protected function applyEditSiteFromPreference()
    {
        if ($site = $this->getBackendPreference()) {
            Site::applyEditSite($site);
        }
    }

    /**
     * applyEditSiteBanner
     */
    protected function applyEditSiteBanner()
    {
        $site = Site::getEditSite();
        if (!$site || !$site->is_styled) {
            return;
        }

        Block::append('banner-area', $this->makePartial('sitebanner', [
            'siteName' => $site->name,
            'foregroundColor' => $site->color_foreground,
            'backgroundColor' => $site->color_background,
            'flagIcon' => $site->flag_icon
        ]));
    }

    /**
     * getBackendPreference
     */
    public function getBackendPreference()
    {
        /**
         * @event backend.site.getEditSite
         * Overrides the edit site object.
         *
         * If a value is returned from this halting event, it will be used as the edit
         * site object. Example usage:
         *
         *     Event::listen('backend.site.getEditSite', function() {
         *         return SiteDefinition::find(1);
         *     });
         *
         */
        $apiResult = Event::fire('backend.site.getEditSite', [], true);
        if ($apiResult !== null) {
            return $apiResult;
        }

        $id = Cookie::get('admin_site');

        if (!$id && BackendAuth::getUser()) {
            $id = UserPreference::forUser()->get('system::site.edit', null);
        }

        if (!$id) {
            return Site::getAnyEditSite();
        }

        return Site::getSiteFromId($id) ?: Site::getAnyEditSite();
    }

    /**
     * storeBackendPreference sets the editing theme
     */
    public function storeBackendPreference($id)
    {
        UserPreference::forUser()->set('system::site.edit', $id);

        Cookie::queue(Cookie::forever('admin_site', $id));

        /**
         * @event backend.site.setEditSite
         * Fires when the edit site has been changed.
         *
         * Example usage:
         *
         *     Event::listen('backend.site.setEditSite', function($id) {
         *         \Log::info("Site has been changed to $id");
         *     });
         *
         */
        Event::fire('backend.site.setEditSite', [$id]);
    }
}
