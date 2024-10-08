<?php namespace Cms\Classes;

use File;
use Lang;
use Cms\Contracts\CmsObject as CmsObjectContract;
use Cms\Helpers\File as FileHelper;
use October\Rain\Extension\Extendable;
use DirectoryIterator;
use SystemException;

/**
 * The CMS component partial class. These objects are read-only.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class ComponentPartial extends Extendable implements CmsObjectContract
{
    /**
     * @var \Cms\Classes\ComponentBase A reference to the CMS component containing the object.
     */
    protected $component;

    /**
     * @var string The component partial file name.
     */
    public $fileName;

    /**
     * @var string Last modified time.
     */
    public $mtime;

    /**
     * @var string Partial content.
     */
    public $content;

    /**
     * @var int The maximum allowed path nesting level. The default value is 5,
     * meaning that files can only exist in the root directory, or in a
     * subdirectory. Set to null if any level is allowed.
     */
    protected $maxNesting = 5;

    /**
     * @var array Allowable file extensions.
     */
    protected $allowedExtensions = ['htm'];

    /**
     * @var string Default file extension.
     */
    protected $defaultExtension = 'htm';

    /**
     * __construct an instance of the object and associates it with a CMS component.
     * @param \Cms\Classes\ComponentBase $component Specifies the component the object belongs to.
     */
    public function __construct(ComponentBase $component)
    {
        $this->component = $component;

        parent::__construct();
    }

    /**
     * load the object from a file.
     * This method is used in the CMS back-end. It doesn't use any caching.
     * @param \Cms\Classes\ComponentBase $component Specifies the component the object belongs to.
     * @param string $fileName Specifies the file name, with the extension.
     * The file name can contain only alphanumeric symbols, dashes and dots.
     * @return mixed Returns a CMS object instance or null if the object wasn't found.
     */
    public static function load($component, $fileName)
    {
        return (new static($component))->find($fileName);
    }

    /**
     * loadCached since there is not much point caching a component partial, so this behavior
     * reverts to a regular load call.
     * @param \Cms\Classes\ComponentBase $component
     * @param string $fileName
     * @return mixed
     */
    public static function loadCached($component, $fileName)
    {
        return static::load($component, $fileName);
    }

    /**
     * loadOverrideCached checks if a partial override exists in the supplied theme and returns it.
     * Since the beginning of time, October inconsistently checked for overrides
     * using the component alias exactly, resulting in a folder with uppercase
     * characters, subsequently this method checks for both variants.
     *
     * @param \Cms\Classes\Theme $theme
     * @param \Cms\Classes\ComponentBase $component
     * @param string $fileName
     * @return mixed
     */
    public static function loadOverrideCached($theme, $component, $fileName)
    {
        $partial = Partial::loadCached($theme, strtolower($component->alias) . '/' . $fileName);

        // Look at second segment of component definition
        if ($partial === null) {
            $partial = Partial::loadCached($theme, $component->alias . '/' . $fileName);
        }

        // Look at first segment if not a PHP class
        if ($partial === null && strpos($component->name, '\\') === false) {
            $partial = Partial::loadCached($theme, $component->name . '/' . $fileName);
        }

        return $partial;
    }

    /**
     * all loads all partials for a given component
     */
    public static function all($component): array
    {
        if (!is_dir($component->getPath())) {
            return [];
        }

        $it = new DirectoryIterator($component->getPath());
        $it->rewind();

        $result = [];
        foreach ($it as $fileinfo) {
            if ($fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }

            $partial = static::load($component, $fileinfo->getFilename());
            if ($partial) {
                $result[] = $partial;
            }
        }

        return $result;
    }

    /**
     * find a single template by its file name.
     *
     * @param  string $fileName
     * @return mixed|static
     */
    public function find($fileName)
    {
        $fileName = $this->validateFileName($fileName);

        $filePath = $this->getFilePath($fileName);

        if (!File::isFile($filePath)) {
            return null;
        }

        if (($content = @File::get($filePath)) === false) {
            return null;
        }

        $this->fileName = $fileName;
        $this->mtime = File::lastModified($filePath);
        $this->content = $content;

        return $this;
    }

    /**
     * check returns true if the specific component contains a matching partial.
     * @param \Cms\Classes\ComponentBase $component Specifies a component the file belongs to.
     * @param string $fileName Specifies the file name to check.
     * @return bool
     */
    public static function check(ComponentBase $component, $fileName)
    {
        $partial = new static($component);

        if (!strlen(File::extension($fileName))) {
            $fileName .= '.' . $partial->getDefaultExtension();
        }

        return File::isFile($partial->getFilePath($fileName));
    }

    /**
     * validateFileName checks the supplied file name for validity.
     * @param string  $fileName
     * @return string
     */
    protected function validateFileName($fileName)
    {
        if (!FileHelper::validatePath($fileName, $this->maxNesting)) {
            throw new SystemException(Lang::get('cms::lang.cms_object.invalid_file', [
                'name' => $fileName
            ]));
        }

        if (!strlen(File::extension($fileName))) {
            $fileName .= '.'.$this->defaultExtension;
        }

        return $fileName;
    }

    /**
     * getContent returns the file content.
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * getTwigContent returns the Twig content string.
     * @return string
     */
    public function getTwigContent()
    {
        return $this->content;
    }

    /**
     * getTwigCacheKey returns the key used by the Twig cache.
     * @return string
     */
    public function getTwigCacheKey()
    {
        return $this->getFilePath();
    }

    /**
     * getFileName returns the file name.
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * getDefaultExtension returns the default extension used by this template.
     * @return string
     */
    public function getDefaultExtension()
    {
        return $this->defaultExtension;
    }

    /**
     * getBaseFileName returns the file name without the extension.
     * @return string
     */
    public function getBaseFileName()
    {
        $pos = strrpos($this->fileName, '.');
        if ($pos === false) {
            return $this->fileName;
        }

        return substr($this->fileName, 0, $pos);
    }

    /**
     * getFilePath returns the absolute file path.
     * @param string $fileName Specifies the file name to return the path to.
     * @return string
     */
    public function getFilePath($fileName = null)
    {
        if ($fileName === null) {
            $fileName = $this->fileName;
        }

        $component = $this->component;
        $path = $component->getPath().'/'.$fileName;

        // Check the shared "/partials" directory for the partial
        if (!File::isFile($path)) {
            $sharedDir = dirname($component->getPath()).'/partials';
            $sharedPath = $sharedDir.'/'.$fileName;
            if (File::isFile($sharedPath)) {
                return $sharedPath;
            }
        }

        return $path;
    }
}
