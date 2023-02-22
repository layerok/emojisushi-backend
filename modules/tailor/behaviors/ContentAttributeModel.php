<?php namespace Tailor\Behaviors;

use Tailor\Classes\ModelBehaviorBase;

/**
 * ContentAttributeModel extension saves content to an attribute
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = [\Tailor\Behaviors\ContentAttributeModel::class];
 *
 */
class ContentAttributeModel extends ModelBehaviorBase
{
    /**
     * __construct the behavior
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->model->bindEvent('model.saveInternal', function() {
            $this->storeBlueprintContent();
        });

        $this->model->bindEvent('model.afterFetch', function() {
            $this->fetchBlueprintContent();
            $this->extendWithBlueprint();
        });

        $this->model->bindEvent('model.newInstance', function($model) {
            $model->extendWithBlueprint($this->model->blueprint_uuid);
        });
    }

    /**
     * fetchBlueprintContent
     */
    public function fetchBlueprintContent()
    {
        $content = $this->model->content;
        $contentColumns = $this->getFieldsetColumnNames();

        // Fetch content attributes
        foreach ($contentColumns as $key) {
            if (array_key_exists($key, $content)) {
                $this->model->$key = $content[$key];
            }
        }
    }

    /**
     * storeBlueprintContent
     */
    public function storeBlueprintContent()
    {
        $content = [];
        $contentColumns = $this->getFieldsetColumnNames();

        // Save attributes to content, purge from model
        $toSave = array_only($this->model->attributes, $contentColumns);
        foreach ($toSave as $key => $value) {
            $content[$key] = $this->model->$key;
            unset($this->model->attributes[$key]);
        }

        $this->model->content = $content;
    }
}
