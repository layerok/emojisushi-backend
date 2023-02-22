<?php namespace Tailor\Classes;

use App;
use File;
use System;
use Tailor\Classes\Blueprint\EntryBlueprint;
use System\Helpers\Cache as CacheHelper;

/**
 * BlueprintIndexer super class responsible for indexing blueprints
 *
 * @method static BlueprintIndexer instance()
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
class BlueprintIndexer
{
    use \System\Traits\NoteMaker;
    use \October\Rain\Support\Traits\Singleton;
    use \Tailor\Classes\BlueprintIndexer\MixinIndex;
    use \Tailor\Classes\BlueprintIndexer\GlobalIndex;
    use \Tailor\Classes\BlueprintIndexer\SectionIndex;
    use \Tailor\Classes\BlueprintIndexer\FieldsetIndex;
    use \Tailor\Classes\BlueprintIndexer\PermissionRegistry;
    use \Tailor\Classes\BlueprintIndexer\NavigationRegistry;
    use \Tailor\Classes\BlueprintIndexer\PageManagerRegistry;

    /**
     * @var array cache collection
     */
    public static $memoryCache = [];

    /**
     * @var int migrateCount number of migrations that occured.
     */
    protected $migrateCount = 0;

    /**
     * @var bool debugChecked for the debug cache buster
     */
    protected $debugChecked = false;

    /**
     * migrate
     */
    public function migrate()
    {
        static::clearCache();

        $this->migrateCount = 0;

        $this->note('Migrating Content Tables');

        $allBlueprints = Blueprint::listInProject();

        // Validate blueprints
        foreach ($allBlueprints as $blueprint) {
            $blueprint->validate();

            // Saving a blueprint will generate a uuid
            if (!$blueprint->uuid) {
                $blueprint->forceSave();
            }
        }

        // Migrate blueprints
        foreach ($allBlueprints as $blueprint) {
            if ($blueprint instanceof EntryBlueprint) {
                $this->migrateContentInternal($blueprint);
            }
        }

        if ($this->migrateCount === 0) {
            $this->note('<info>Nothing to migrate.</info>');
        }
    }

    /**
     * migrateBlueprint
     */
    public function migrateBlueprint(Blueprint $blueprint)
    {
        static::clearCache();

        // Saving a blueprint will generate a uuid
        if (!$blueprint->uuid) {
            $blueprint->forceSave();
        }

        if ($blueprint instanceof EntryBlueprint) {
            $this->migrateContentInternal($blueprint);
        }
    }

    /**
     * migrateContentInternal
     */
    protected function migrateContentInternal(Blueprint $blueprint)
    {
        if ($fieldset = $this->findContentFieldset($blueprint->uuid)) {
            if (SchemaBuilder::migrateBlueprint($blueprint, $fieldset)) {
                $this->note('- <info>'.$blueprint->name.'</info>: '.$blueprint->handle .' ['.$blueprint->getContentTableName().']');
                $this->migrateCount++;
            }
        }
    }

    /**
     * getCache
     */
    protected function getCache($name): array
    {
        if (App::runningUnitTests()) {
            return [];
        }

        if (System::checkDebugMode()) {
            $this->resetCacheInDebugMode();
        }

        if (array_key_exists($name, static::$memoryCache)) {
            return static::$memoryCache[$name];
        }

        $fileName = $this->makeCacheFile($name);

        if (!File::exists($fileName)) {
            return [];
        }

        $result = File::getRequire($fileName);
        if (!is_array($result)) {
            return [];
        }

        return static::$memoryCache[$name] = $result;
    }

    /**
     * resetCacheInDebugMode
     */
    protected function resetCacheInDebugMode()
    {
        if ($this->debugChecked) {
            return;
        }

        if (!file_exists(app_path('blueprints'))) {
            return;
        }

        $currentMtime = 0;
        $mtime = File::lastModifiedRecursive(app_path('blueprints'));
        $debugFile = $this->makeCacheFile('debug');

        if (file_exists($debugFile)) {
            $currentMtime = File::getRequire($debugFile)['mtime'] ?? 0;
        }

        if ($mtime > $currentMtime) {
            $this->clearCache();
        }

        File::put(
            $debugFile,
            '<?php return '.var_export(compact('mtime'), true).';'
        );

        $this->debugChecked = true;
    }

    /**
     * putCache
     */
    protected function putCache($name, array $contents): void
    {
        File::put(
            $this->makeCacheFile($name),
            '<?php return '.var_export($contents, true).';'
        );
    }

    /**
     * flushCache clears the memory cache
     */
    public static function flushCache()
    {
        static::$memoryCache = [];
    }

    /**
     * makeCacheFile
     */
    protected function makeCacheFile($name): string
    {
        return cache_path("cms/blueprint-{$name}.php");
    }

    /**
     * clearCache clears the disk cache
     */
    public static function clearCache()
    {
        CacheHelper::instance()->clearBlueprintCache();
    }
}
