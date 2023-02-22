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
            $types[$this->pageManagerBlueprintToType($blueprint)] = $blueprint->name;
        }

        return $types;
    }

    /**
     * getPageManagerTypeInfo
     */
    public function getPageManagerTypeInfo($type): array
    {
        if ($record = $this->pageManagerTypeToModel($type)) {
            return [
                'references' => $this->listRecordOptionsForPageInfo($record),
                'cmsPages' => $this->listBlueprintCmsPagesForPageInfo($record)
            ];
        }

        return [];
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

        $iterator = function($children) use (&$iterator, &$item, &$theme, $url) {
            $branch = [];

            foreach ($children as $child) {
                $childUrl = $this->getPageManagerPageUrl($item->cmsPage, $child, $theme);

                $childItem = [
                    'url' => $childUrl,
                    'isActive' => $childUrl == $url,
                    'title' => $child->title,
                    'mtime' => $child->updated_at,
                ];

                if ($child->children) {
                    $childItem['items'] = $iterator($child->children);
                }

                $branch[] = $childItem;
            }

            return $branch;
        };

        $result['items'] = $iterator($model->children);

        return $result;
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
                return $code . '-' . str_replace('_', '-', $blueprint->uuid);
            }
        }

        return '';
    }
}
