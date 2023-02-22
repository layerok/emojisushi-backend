<?php namespace Tailor\Classes;

/**
 * RecordIndexer super class responsible for indexing record models
 *
 * @method static RecordIndexer instance()
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
class RecordIndexer
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * process
     */
    public function process($model)
    {
        if ($model->isEntryStructure()) {
            $this->processFullSlug($model);
        }
    }

    /**
     * processFullSlug
     */
    public function processFullSlug($model)
    {
        $proposedSlug = $this->makeFullSlugPath($model);

        if ($model->fullslug != $proposedSlug) {
            $model->fullslug = $proposedSlug;
            $model->save();
        }

        if ($children = $model->children) {
            foreach ($children as $child) {
                $this->processFullSlug($child);
            }
        }
    }

    /**
     * makeFullSlugPath
     */
    protected function makeFullSlugPath($model, $fullslug = '')
    {
        $fullslug = $model->slug . '/' . $fullslug;

        if ($parent = $model->parent()->withoutGlobalScopes()->first()) {
            $fullslug = $this->makeFullSlugPath($parent, $fullslug);
        }

        return rtrim($fullslug, '/');
    }
}
