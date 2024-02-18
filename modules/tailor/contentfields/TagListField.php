<?php namespace Tailor\ContentFields;

use Backend\FormWidgets\TagList;
use October\Contracts\Element\ListElement;
use October\Contracts\Element\FilterElement;

/**
 * TagListField
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
class TagListField extends FallbackField
{
    /**
     * defineListColumn
     */
    public function defineListColumn(ListElement $list, $context = null)
    {
        if (is_array($this->column)) {
            $list->defineColumn($this->fieldName, $this->label)
                ->displayAs($this->isModeUsingArray() ? 'selectable' : 'text')
                ->shortLabel($this->shortLabel)
                ->options($this->options)
                ->useConfig($this->column ?: [])
            ;
        }
    }

    /**
     * defineFilterScope
     */
    public function defineFilterScope(FilterElement $filter, $context = null)
    {
        if (is_array($this->scope)) {
            // @deprecated move to the filter class. detect list array and combine there (v4)
            $options = $this->options;
            if ($options && !$this->useKey) {
                $options = array_combine($this->options, $this->options);
            }

            $filter->defineScope($this->fieldName, $this->label)
                ->displayAs($this->isModeUsingArray() ? 'group' : 'text')
                ->shortLabel($this->shortLabel)
                ->options($options)
                ->useConfig($this->scope ?: [])
            ;
        }
    }

    /**
     * extendModelObject will extend the record model.
     */
    public function extendModelObject($model)
    {
        if ($this->isModeUsingArray()) {
            $model->addJsonable($this->fieldName);
        }
    }

    /**
     * extendDatabaseTable adds any required columns to the database.
     */
    public function extendDatabaseTable($table)
    {
        // @deprecated should be mediumText in v4
        $table->text($this->fieldName)->nullable();
        // $table->mediumText($this->fieldName)->nullable();
    }

    /**
     * isModeUsingArray
     */
    protected function isModeUsingArray()
    {
        // @deprecated default to array in v4
        if (!$this->mode) {
            return false;
        }

        return !$this->mode || $this->mode === TagList::MODE_ARRAY;
    }
}
