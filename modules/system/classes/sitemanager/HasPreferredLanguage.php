<?php namespace System\Classes\SiteManager;

/**
 * HasPreferredLanguage implements browser detection logic
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasPreferredLanguage
{
    /**
     * getSiteFromBrowser locates the site based on the browser locale, e.g. HTTP_ACCEPT_LANGUAGE
     */
    public function getSiteFromBrowser(string $acceptLanguage, $groupId = null)
    {
        $locales = $this->getLocalesFromBrowser($acceptLanguage);

        $sites = $this->listEnabled()->inGroup($groupId);

        foreach ($locales as $locale => $priority) {
            if ($foundSite = $sites->inLocale($locale)->first()) {
                return $foundSite;
            }
        }

        return $sites->first();
    }

    /**
     * getLocalesFromBrowser based on an accepted string, e.g. en-GB,en-US;q=0.9,en;q=0.8
     * Returns a sorted array in format of `[(string) locale => (float) priority]`
     */
    public function getLocalesFromBrowser(string $acceptedStr): array
    {
        $result = $matches = [];
        $acceptedStr = strtolower($acceptedStr);

        // Find explicit matches
        preg_match_all('/([\w-]+)(?:[^,\d]+([\d.]+))?/', $acceptedStr, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $locale = $match[1] ?? '';
            $priority = (float) ($match[2] ?? 1.0);

            if ($locale) {
                $result[$locale] = $priority;
            }
        }

        // Estimate other locales by popping off the region (en-us -> en)
        foreach ($result as $locale => $priority) {
            $shortLocale = explode('-', $locale)[0];
            if ($shortLocale !== $locale && !array_key_exists($shortLocale, $result)) {
                $result[$shortLocale] = $priority - 0.1;
            }
        }

        arsort($result);
        return $result;
    }

    /**
     * @deprecated use Site::listEnabled()->inLocale()->first()
     */
    public function getSiteForLocale(string $locale)
    {
        return $this->listEnabled()->inLocale($locale)->first();
    }
}
