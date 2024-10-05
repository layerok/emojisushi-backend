<?php namespace Cms\Classes;

use App;
use Cms;
use Site;
use Config;
use Request;
use Redirect;
use Illuminate\Routing\Controller as ControllerBase;
use Closure;

/**
 * CmsController is the master controller for all front-end pages.
 * All requests that have not been picked up already by the router will end up here,
 * then the URL is passed to the front-end controller for processing.
 *
 * @see Cms\Classes\Controller Front-end controller class
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class CmsController extends ControllerBase
{
    use \October\Rain\Extension\ExtendableTrait;

    /**
     * @var array implement behaviors in this controller.
     */
    public $implement;

    /**
     * __construct a new CmsController instance.
     */
    public function __construct()
    {
        $this->extendableConstruct();
    }

    /**
     * extend this object properties upon construction.
     * @param Closure $callback
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * run finds and serves the request using the primary controller. Specifies the
     * requested page URL. If the parameter is omitted, the current URL used.
     * Returns the processed page content.
     * @param string $url
     * @return string
     */
    public function run($url = '/')
    {
        // Check configuration for bypass exceptions
        if (Cms::urlHasException((string) $url, 'site')) {
            return App::make(Controller::class)->run($url);
        }

        // Locate site
        $site = $this->findSite(Request::root(), $url);

        // Remove prefix, if applicable
        $uri = $this->parseUri($site, $url);

        // Enforce prefix, if applicable
        if ($redirect = $this->redirectWithoutPrefix($site, $url, $uri)) {
            return $redirect;
        }

        return App::make(Controller::class)->run($uri);
    }

    /**
     * findSite locates the site based on the current URL
     */
    protected function findSite(string $rootUrl, string $url)
    {
        $site = Site::getSiteFromRequest($rootUrl, $url);

        if (!$site || !$site->is_enabled) {
            abort(404);
        }

        Site::applyActiveSite($site);

        return $site;
    }

    /**
     * parseUri removes the prefix from a URL
     */
    protected function parseUri($site, string $url): string
    {
        return $site ? $site->removeRoutePrefix($url) : $url;
    }

    /**
     * redirectWithoutPrefix redirects if a prefix is enforced
     */
    protected function redirectWithoutPrefix($site, string $originalUrl, string $proposedUrl)
    {
        // Only a fallback site should redirect
        if (!$site || !$site->is_prefixed || !$site->isFallbackMatch) {
            return null;
        }

        // A prefix has been found and removed already
        if ($originalUrl !== '/' && $originalUrl !== $proposedUrl) {
            return null;
        }

        // Apply redirect policy
        $redirectUrl = $this->determineRedirectFromPolicy($site, $originalUrl);
        if (!$redirectUrl) {
            abort(404);
        }

        // Preserve query string
        if ($queryString = Request::getQueryString()) {
            $redirectUrl .= '?'.$queryString;
        }

        // No prefix detected, attach one with redirect
        return Redirect::to($redirectUrl, 301);
    }

    /**
     * determineRedirectFromPolicy returns a site based on the configuration
     */
    protected function determineRedirectFromPolicy($originalSite, $originalUrl)
    {
        $policy = Config::get('cms.redirect_policy', 'detect');

        // Detect site from browser locale (same site)
        if ($policy === 'detect') {
            return Site::getSiteFromBrowser(
                (string) Request::server('HTTP_ACCEPT_LANGUAGE'),
                $originalSite->group_id
            )?->attachRoutePrefix($originalUrl);
        }

        // Use primary site
        if ($policy === 'primary') {
            return Site::getPrimarySite()?->base_url;
        }

        // Use a specified site ID
        return Site::getSiteFromId($policy)?->base_url;
    }
}
