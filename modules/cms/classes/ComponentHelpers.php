<?php namespace Cms\Classes;

use Arr;

/**
 * ComponentHelpers defines some component helpers for the CMS UI.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ComponentHelpers
{
    /**
     * getComponentsPropertyConfig returns a component property configuration as a JSON string or array.
     * @param mixed $component The component object
     * @param boolean $addAliasProperty Determines if the Alias property should be added to the result.
     * @param boolean $returnArray Determines if the method should return an array.
     * @return string
     */
    public static function getComponentsPropertyConfig($component, $addAliasProperty = true, $returnArray = false)
    {
        $result = [];

        if ($addAliasProperty) {
            $property = [
                'property' => 'oc.alias',
                'title' => __("Alias"),
                'description' => __("A unique name given to this component when using it in the page or layout code."),
                'type' => 'string',
                'validationPattern' => '^[a-zA-Z]+[0-9a-z\_]*$',
                'validationMessage' => __("Component aliases are required and can contain only Latin symbols, digits, and underscores. The aliases should start with a Latin symbol."),
                'required' => true,
                'showExternalParam' => false
            ];
            $result[] = $property;
        }

        $properties = $component->defineProperties();
        if (is_array($properties)) {
            foreach ($properties as $name => $params) {
                $property = [
                    'property' => $name,
                    'title' => array_get($params, 'title', $name),
                    'type' => array_get($params, 'type', 'string'),
                    'showExternalParam' => array_get($params, 'showExternalParam', true)
                ];

                foreach ($params as $name => $value) {
                    if (isset($property[$name])) {
                        continue;
                    }
                    $property[$name] = $value;
                }

                // Translate human values
                $toTranslate = ['title', 'description', 'options', 'group', 'validationMessage'];
                foreach ($property as $name => $value) {
                    if (!in_array($name, $toTranslate)) {
                        continue;
                    }

                    if (is_array($value)) {
                        array_walk($property[$name], function (&$_value, $key) {
                            $_value = __($_value);
                        });
                    }
                    else {
                        $property[$name] = __($value);
                    }
                }

                // Convert nested properties
                $toNestProperty = ['itemProperties', 'properties'];
                foreach ($property as $name => $value) {
                    if (!in_array($name, $toNestProperty)) {
                        continue;
                    }

                    if (!is_array($value) || Arr::isList($value)) {
                        continue;
                    }

                    $newValue = [];
                    foreach ($value as $_name => $_props) {
                        $newValue[] = [
                            'property' => $_name
                        ] + $_props;
                    }
                    $property[$name] = $newValue;
                }

                $result[] = $property;
            }
        }

        if ($returnArray) {
            return $result;
        }

        return json_encode($result);
    }

    /**
     * getComponentPropertyValues returns a component property values.
     * @param mixed $component The component object
     * @param boolean $returnArray Returns array if TRUE. Returns JSON string otherwise.
     * @return mixed
     */
    public static function getComponentPropertyValues($component, $returnArray = false)
    {
        $result = [];

        $result['oc.alias'] = $component->alias;

        $properties = $component->defineProperties();
        if (is_array($properties)) {
            foreach ($properties as $name => $params) {
                $result[$name] = $component->property($name);
            }
        }

        if ($returnArray) {
            return $result;
        }

        return json_encode($result);
    }

    /**
     * getComponentName returns a component name.
     * @param mixed $component The component object
     * @return string
     */
    public static function getComponentName($component)
    {
        return __($component->componentDetails()['name'] ?? "Unnamed");
    }

    /**
     * getComponentDescription returns a component description.
     * @param mixed $component The component object
     * @return string
     */
    public static function getComponentDescription($component)
    {
        return __($component->componentDetails()['description'] ?? "No description provided");
    }
}
