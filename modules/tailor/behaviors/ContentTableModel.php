<?php namespace Tailor\Behaviors;

use Tailor\Classes\ModelBehaviorBase;

/**
 * ContentTableModel extension saves content to a dedicated table
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = [\Tailor\Behaviors\ContentTableModel::class];
 *
 */
class ContentTableModel extends ModelBehaviorBase
{
    /**
     * __construct the behavior
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->model->bindEvent('model.afterFetch', function() {
            $this->extendWithBlueprint();
        });

        $this->model->bindEvent('model.newInstance', function($model) {
            $model->extendWithBlueprint($this->model->blueprint_uuid);
        });
    }
}
