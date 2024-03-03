<?php namespace System\Classes\SiteManager;

use App;
use System\Models\SiteDefinition;
use Closure;

/**
 * HasSiteContext
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasSiteContext
{
    /**
     * @var bool globalContext disables site filters globally.
     */
    protected $globalContext = false;

    /**
     * @var SiteDefinition|null siteContext overrides the current site context.
     */
    protected $siteContext = null;

    /**
     * listSiteIdsInGroup
     */
    public function listSiteIdsInGroup($siteId = null)
    {
        $site = $siteId ? $this->getSiteFromId($siteId) : $this->getSiteFromContext();

        if ($groupId = $site?->group_id) {
            return $this->listSites()->where('group_id', $groupId)->pluck('id')->all();
        }

        return $this->listSiteIds();
    }

    /**
     * listSiteIdsInLocale
     */
    public function listSiteIdsInLocale($siteId = null)
    {
        $site = $siteId ? $this->getSiteFromId($siteId) : $this->getSiteFromContext();

        if ($localeCode = $site?->hard_locale) {
            return $this->listSites()->where('locale', $localeCode)->pluck('id')->all();
        }

        return [];
    }

    /**
     * getSiteIdFromContext
     * @return int|null
     */
    public function getSiteIdFromContext()
    {
        $site = $this->getSiteFromContext();

        if (!$site || !$site->id) {
            return null;
        }

        return (int) $site->id;
    }

    /**
     * getSiteCodeFromContext
     * @return string|null
     */
    public function getSiteCodeFromContext()
    {
        $site = $this->getSiteFromContext();

        if (!$site || !$site->code) {
            return null;
        }

        return (string) $site->code;
    }

    /**
     * getSiteFromContext
     * @return SiteDefinition
     */
    public function getSiteFromContext()
    {
        if ($this->siteContext !== null) {
            return $this->siteContext;
        }

        return App::runningInBackend()
            ? $this->getEditSite()
            : $this->getActiveSite();
    }

    /**
     * hasGlobalContext
     */
    public function hasGlobalContext(): bool
    {
        return $this->globalContext;
    }

    /**
     * withGlobalContext
     */
    public function withGlobalContext(Closure $callback)
    {
        $previous = $this->globalContext;

        $this->globalContext = true;

        try {
            return $callback();
        }
        finally {
            $this->globalContext = $previous;
        }
    }

    /**
     * withContext
     */
    public function withContext($siteId, Closure $callback)
    {
        $previous = $this->siteContext;

        $site = $this->getSiteFromId($siteId);

        if ($site) {
            $this->broadcastSiteChange($site->id);
        }

        try {
            $this->siteContext = $site;

            return $callback();
        }
        finally {
            $this->siteContext = $previous;

            if ($previousId = $this->getSiteIdFromContext()) {
                $this->broadcastSiteChange($previousId);
            }
        }
    }

    /**
     * @deprecated
     */
    public function listSiteIdsInContext()
    {
        return $this->listSiteIdsInGroup();
    }
}
