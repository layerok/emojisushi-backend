<?php namespace System\Facades;

use October\Rain\Support\Facade;

/**
 * Manifest facade
 *
 * @method static bool hasModule(string $name)
 * @method static array listModules()
 * @method static bool hasDatabase()
 * @method static bool checkDebugMode()
 * @method static bool checkSafeMode()
 * @method static string composerToOctoberCode(string $name)
 * @method static string octoberToComposerCode(string $name, string $type, bool $prefix)
 *
 * @see \System\Classes\ManifestCache
 */
class Manifest extends Facade
{
    /**
     * getFacadeAccessor gets the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'system.manifest';
    }
}
