<?php namespace Backend\FormWidgets\Repeater;

/**
 * HasJsonStore contains logic for related repeater items
 */
trait HasJsonStore
{
    /**
     * processItemsForJson processes data and applies it to the form widgets
     */
    protected function processItemsForJson()
    {
        $currentValue = $this->getLoadValue();

        // Pad current value with minimum items and disable for groups,
        // which cannot predict their item types
        if (!$this->useGroups && $this->minItems > 0) {
            if (!is_array($currentValue)) {
                $currentValue = [];
            }

            if (count($currentValue) < $this->minItems) {
                $currentValue = array_pad($currentValue, $this->minItems, []);
            }
        }

        if (!is_array($currentValue)) {
            return;
        }

        // Load up the necessary form widgets
        foreach ($currentValue as $index => $value) {
            $this->makeItemFormWidget(
                $index,
                $this->getGroupCodeFromJson($value)
            );
        }
    }

    /**
     * processSaveForJson
     */
    protected function processSaveForJson($value)
    {
        if (!is_array($value) || !$value) {
            return null;
        }

        foreach ($value as $index => $data) {
            if (!isset($this->formWidgets[$index])) {
                continue;
            }

            // Give repeated form field widgets an opportunity to process the data.
            $widget = $this->formWidgets[$index];
            $value[$index] = $widget->getSaveData();

            if ($this->useGroups) {
                $this->setGroupCodeOnJson($value[$index], $data[$this->groupKeyFrom] ?? '');
            }
        }

        return array_values($value);
    }

    /**
     * getGroupCodeFromJson
     */
    protected function getGroupCodeFromJson($value)
    {
        return array_get($value, $this->groupKeyFrom, null);
    }

    /**
     * setGroupCodeOnJson
     */
    protected function setGroupCodeOnJson(&$value, $groupCode)
    {
        $value[$this->groupKeyFrom] = $groupCode;
    }
}
