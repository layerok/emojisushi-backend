<?php namespace Cms\Components;

use Site;
use Event;
use October\Rain\Router\Router as RainRouter;
use Cms\Classes\ComponentModuleBase;

/**
 * SitePicker component
 */
class SitePicker extends ComponentModuleBase
{
    /**
     * @var array sitesCache for multiple calls
     */
    protected $sitesCache;

    /**
     * componentDetails
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name' => 'Site Picker',
            'description' => 'Displays links for selecting a different site.',
        ];
    }

    /**
     * isEnabled returns true if the site picker should be displayed
     */
    public function isEnabled()
    {
        return Site::hasMultiSite();
    }

    /**
     * sites lazily loads the available sites
     */
    public function sites()
    {
        if ($this->sitesCache !== null) {
            return $this->sitesCache;
        }

        $sites = Site::listEnabled();

        foreach ($sites as $site) {
            $site->setUrlOverride($this->makeUrlForSite($site));
        }

        return $this->sitesCache = $sites;
    }

    /**
     * makeUrlForSite
     */
    protected function makeUrlForSite($site)
    {
        $pattern = $this->getPatternFromPage($site);

        $url = $this->withPreservedQueryString(
            $this->getUrlFromPattern($pattern, $site),
            $site
        );

        return $url;
    }

    /**
     * getPatternFromPage
     */
    protected function getPatternFromPage($site)
    {
        $page = $this->getPage();
        $pattern = $page->url;

        /**
         * @event cms.sitePicker.overridePattern
         * Enables manipulating the URL route  pattern
         *
         * You will have access to the page object, the old and new locale and the URL pattern.
         *
         * Example usage:
         *
         *     Event::listen('cms.sitePicker.overridePattern', function($page, $pattern, $currentSite, $proposedSite) {
         *        if ($page->baseFileName == 'your-page-filename') {
         *             return YourModel::overridePattern($pattern, $currentSite, $proposedSite);
         *         }
         *     });
         *
         */
        $translatedPattern = Event::fire('cms.sitePicker.overridePattern', [
            $page,
            $pattern,
            Site::getActiveSite(),
            $site
        ], true);

        if ($translatedPattern) {
            $pattern = $translatedPattern;
        }

        return $pattern;
    }

    /**
     * getUrlFromPattern
     */
    protected function getUrlFromPattern($urlPattern, $site)
    {
        $params = $this->getRouter()->getParameters();

        /**
         * @event cms.sitePicker.overrideParams
         * Enables manipulating the URL parameters
         *
         * You will have access to the page object, the old and new locale and the URL parameters.
         *
         * Example usage:
         *
         *     Event::listen('cms.sitePicker.overrideParams', function($page, $params, $currentSite, $proposedSite) {
         *        if ($page->baseFileName == 'your-page-filename') {
         *             return YourModel::overrideParams($params, $currentSite, $proposedSite);
         *         }
         *     });
         *
         */
        $translatedParams = Event::fire('cms.sitePicker.overrideParams', [
            $this->getPage(),
            $params,
            Site::getActiveSite(),
            $site
        ], true);

        if ($translatedParams) {
            $params = $translatedParams;
        }

        $router = new RainRouter;

        $path = $router->urlFromPattern($urlPattern, $params);

        return rtrim($site->base_url . $path, '/');
    }

    /**
     * withPreservedQueryString makes sure to add any existing query string to the redirect url.
     * @param $url
     * @param $site
     * @return string
     */
    protected function withPreservedQueryString($url, $site)
    {
        $query = get();

        /**
         * @event cms.sitePicker.overrideQuery
         * Enables manipulating the URL query parameters
         *
         * You will have access to the page object, the old and new site and the URL query parameters.
         *
         * Example usage:
         *
         *     Event::listen('cms.sitePicker.overrideQuery', function($page, $params, $currentSite, $proposedSite) {
         *        if ($page->baseFileName == 'your-page-filename') {
         *             return YourModel::translateQuery($params, $currentSite, $proposedSite);
         *         }
         *     });
         *
         */
        $translatedQuery = Event::fire('cms.sitePicker.overrideQuery', [
            $this->getPage(),
            $query,
            Site::getActiveSite(),
            $site
        ], true);

        if ($translatedQuery) {
            $query = $translatedQuery;
        }

        $queryStr = http_build_query($query);

        return $queryStr ? $url . '?' . $queryStr : $url;
    }
}
