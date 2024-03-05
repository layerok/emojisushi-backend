<?php namespace Backend\Behaviors\RelationController;

use Form as FormHelper;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * HasNestedRelations implements nested relation chains
 */
trait HasNestedRelations
{
    /**
     * @var array manageIds provided by the relationship chain
     */
    protected $manageIds = [];

    /**
     * @var array sessionKeys provided by the relationship chain
     */
    protected $sessionKeys = [];

    /**
     * getSessionKeysForField returns a session key for saving a form and for binding a relation,
     * the result is an array with [formSessionKey, relationSessionKey]
     */
    protected function getSessionKeysForField($field): array
    {
        if (isset($this->sessionKeys[$field])) {
            return $this->sessionKeys[$field];
        }

        $sessionKey = post('_session_key', FormHelper::getSessionKey());
        $relationSessionKey = post('_relation_session_key', $sessionKey);

        return $this->sessionKeys[$field] = [$sessionKey, $relationSessionKey];
    }

    /**
     * getManageIdForField
     */
    protected function getManageIdForField($field)
    {
        // Field checksum for manage id
        if ($field === post('_relation_field') && ($manageId = post('manage_id', -1)) !== -1) {
            return $this->manageIds[$field] = $manageId;
        }

        if (isset($this->manageIds[$field])) {
            return $this->manageIds[$field];
        }

        return null;
    }

    /**
     * initNestedRelation checks the extra configuration for a relationship chain
     * and binds the manage forms to the controller, which may contain additional
     * relation definitions via the relation form widget.
     */
    protected function initNestedRelation($model, $parentField)
    {
        // Process session keys
        $sessionKeys = $this->extraConfig['sessionKeys'] ?? [];
        foreach ($sessionKeys as $field => $keys) {
            if (is_array($keys)) {
                $this->sessionKeys[$field] = $keys;
            }
        }

        // Process manage IDs
        $manageIds = $this->extraConfig['manageIds'] ?? [];
        foreach ($manageIds as $field => $id) {
            $this->manageIds[$field] = $id;
        }

        // Process nesting chain
        $checked = [];
        $checked[$parentField] = true;
        $chain = $this->extraConfig['chain'] ?? [];
        foreach ($chain as $field) {
            if (!$field || isset($checked[$field])) {
                continue;
            }

            $this->initRelationInternal($model, $field);
            $checked[$field] = true;
        }
    }

    /**
     * makeNestedRelationModel resolves a relation based on a nested field name
     * E.g: model[relation1][relation2] → $model->relation1()->relation2()
     */
    protected function makeNestedRelationModel($model, $field)
    {
        if (!str_contains($field, '[') || !str_contains($field, ']')) {
            return [$model, $field];
        }

        // This function only supports relations with a single hop
        $parts = HtmlHelper::nameToArray($field);
        $lastField = array_pop($parts);
        $parentParts = $parts;
        while ($rootField = array_shift($parts)) {
            $model = $model->$rootField()->getRelated();
        }

        // Locate parent field, with an expected relation defined in the chain
        $rootParentField = array_shift($parentParts);
        $parentField = $rootParentField;
        if ($parentParts) {
            $parentField .= '['.implode('][', $parentParts).']';
        }

        // Populate the parent model
        $parentId = $this->manageIds[$parentField] ?? null;
        if ($parentId && ($manageModel = $model->find($parentId))) {
            $model = $manageModel;
        }

        return [$model, $lastField];
    }
}
