<?php namespace Tailor\Classes\BlueprintIndexer;

use Cms\Classes\Page;
use Cms\Classes\Controller;
use Tailor\Classes\Blueprint\EntryBlueprint;
use Tailor\Models\EntryRecord;

/**
 * PageManagerRegistry
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
trait PageManagerRegistry
{
    /**
     * listPrimaryNavigation
     */
    public function listPageManagerTypes(): array
    {
        $types = [];

        // Sections
        foreach (EntryBlueprint::listInProject() as $blueprint) {
            if ($typeCode = $this->pageManagerBlueprintToType($blueprint)) {
                if ($blueprint->usePageFinder()) {
                    $types[$typeCode] = $blueprint->getMessage('pagefinderItemType', ":name Entry", ['name' => $blueprint->name]);
                }

                if ($blueprint->usePageFinder('list')) {
                    $types['list-'.$typeCode] = [
                        $blueprint->getMessage('pagefinderListType', "All :name Entries", ['name' => $blueprint->name]),
                        true
                    ];
                }
            }
        }

        return $types;
    }

    /**
     * getPageManagerTypeInfo
     */
    public function getPageManagerTypeInfo($type): array
    {
        $record = $this->pageManagerTypeToModel($type);
        if (!$record) {
            return [];
        }

        $result = [];

        if (!starts_with($type, 'list-')) {
            $result['references'] = $this->listRecordOptionsForPageInfo($record);
        }

        $result['cmsPages'] = $this->listBlueprintCmsPagesForPageInfo($record);

        return $result;
    }

    /**
     * listBlueprintCmsPagesForPageInfo
     */
    protected function listBlueprintCmsPagesForPageInfo($record)
    {
        $handle = $record->blueprint->handle ?? $record->blueprint_uuid;
        return Page::whereComponent('section', 'handle', $handle)->all();
    }

    /**
     * listRecordOptionsForPageInfo
     */
    protected function listRecordOptionsForPageInfo($record)
    {
        $records = $record->isClassInstanceOf(\October\Contracts\Database\TreeInterface::class)
            ? $record->getNested()
            : $record->get();

        $iterator = function($records) use (&$iterator) {
            $result = [];
            foreach ($records as $record) {
                $id = $record->site_root_id ?: $record->id;
                if (!$record->children) {
                    $result[$id] = $record->title;
                }
                else {
                    $result[$id] = [
                        'title' => $record->title,
                        'items' => $iterator($record->children)
                    ];
                }
            }
            return $result;
        };

        return $iterator($records);
    }

    /**
     * resolvePageManagerItem
     */
    public function resolvePageManagerItem($type, $item, $url, $theme): array
    {
        if (starts_with($type, 'list-')) {
            return $this->resolvePageManagerItemAsList($type, $item, $url, $theme);
        }

        return $this->resolvePageManagerItemAsReference($type, $item, $url, $theme);
    }

    /**
     * resolvePageManagerItemAsList
     */
    protected function resolvePageManagerItemAsList($type, $item, $url, $theme): array
    {
        $record = $this->pageManagerTypeToModel($type);
        if (!$record) {
            return [];
        }

        $result = [];

        $records = $record->isClassInstanceOf(\October\Contracts\Database\TreeInterface::class)
            ? $record->getNested()
            : $record->get();

        $recurse = $record->isEntryStructure() && $item->nesting;

        $result['items'] = $this->resolvePageManagerItemAsChildren($records, $item, $theme, $url, $recurse);

        return $result;
    }

    /**
     * resolvePageManagerItemAsReference
     */
    protected function resolvePageManagerItemAsReference($type, $item, $url, $theme): array
    {
        $record = $this->pageManagerTypeToModel($type);
        if (!$record) {
            return [];
        }

        $model = $record->find($item->reference);
        if (!$model) {
            return [];
        }

        $pageUrl = $this->getPageManagerPageUrl($item->cmsPage, $model, $theme);

        $result = [
            'url' => $pageUrl,
            'isActive' => $pageUrl == $url,
            'mtime' => $model->updated_at,
        ];

        if (!$model->isEntryStructure() || !$item->nesting) {
            return $result;
        }

        $result['items'] = $this->resolvePageManagerItemAsChildren($model->children, $item, $theme, $url);

        return $result;
    }

    /**
     * resolvePageManagerItemAsChildren
     */
    protected function resolvePageManagerItemAsChildren($children, $item, $theme, $url, $recursive = true)
    {
        $branch = [];

        foreach ($children as $child) {
            $childUrl = $this->getPageManagerPageUrl($item->cmsPage, $child, $theme);

            $childItem = [
                'url' => $childUrl,
                'isActive' => $childUrl == $url,
                'title' => $child->title,
                'mtime' => $child->updated_at,
            ];

            if ($recursive && $child->children) {
                $childItem['items'] = $this->resolvePageManagerItemAsChildren(
                    $child->children,
                    $item,
                    $theme,
                    $url,
                    $recursive
                );
            }

            $branch[] = $childItem;
        }

        return $branch;
    }

    /**
     * getPageManagerPageUrl
     */
    protected static function getPageManagerPageUrl($pageCode, $record, $theme)
    {
        $controller = Controller::getController() ?: new Controller($theme);

        $url = $controller->pageUrl($pageCode, [
            'id' => $record->id,
            'slug' => $record->slug,
            'fullslug' => $record->fullslug
        ]);

        return $url;
    }

    /**
     * pageManagerTypeToModel
     */
    protected function pageManagerTypeToModel(string $typeName)
    {
        $typesToModel = [
            'entry' => [EntryRecord::class, 'inSectionUuid']
        ];

        if (starts_with($typeName, 'list-')) {
            $typeName = substr($typeName, 5);
        }

        foreach ($typesToModel as $code => $callable) {
            if (starts_with($typeName, $code . '-')) {
                $uuid = substr($typeName, strlen($code) + 1);
                return $callable($uuid);
            }
        }

        return null;
    }

    /**
     * pageManagerBlueprintToType
     */
    protected function pageManagerBlueprintToType($blueprint): string
    {
        $modelsToType = [
            'entry' => EntryBlueprint::class
        ];

        foreach ($modelsToType as $code => $class) {
            if (is_a($blueprint, $class)) {
                return $code . '-' . $blueprint->uuid;
            }
        }

        return '';
    }
}
