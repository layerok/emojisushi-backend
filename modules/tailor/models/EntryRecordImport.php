<?php namespace Tailor\Models;

use Backend\Models\ImportModel;
use October\Contracts\Element\ListElement;
use October\Contracts\Element\FormElement;
use Tailor\Classes\BlueprintIndexer;
use Tailor\Classes\Fieldset;
use SystemException;

/**
 * EntryRecordImport for importing entries
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
class EntryRecordImport extends ImportModel
{
    use \Tailor\Models\EntryRecord\HasBlueprintTypes;

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * setBlueprintUuid
     */
    public function setBlueprintUuid($value)
    {
        $this->blueprint_uuid = $value;
    }

    /**
     * getContentFieldsetDefinition
     */
    public function getContentFieldsetDefinition(): Fieldset
    {
        $fieldset = BlueprintIndexer::instance()->findContentFieldset($this->blueprint_uuid);

        if (!$fieldset) {
            throw new SystemException("Unable to find content fieldset definition with UUID of '{$this->blueprint_uuid}'.");
        }

        return $fieldset;
    }

    /**
     * defineListColumns
     */
    public function defineListColumns(ListElement $host)
    {
        $host->defineColumn('id', 'ID');
        $host->defineColumn('title', 'Title');
        $host->defineColumn('slug', 'Slug');
        $host->defineColumn('is_enabled', 'Enabled');
        $host->defineColumn('published_at', 'Publish Date');
        $host->defineColumn('expired_at', 'Expiry Date');
        $host->defineColumn('content_group', 'Entry Type');

        if ($this->isEntryStructure()) {
            $host->defineColumn('parent_id', 'Parent');
        }

        $this->getContentFieldsetDefinition()->defineAllListColumns($host, ['context' => 'import']);
    }

    /**
     * defineFormFields
     */
    public function defineFormFields(FormElement $host)
    {
    }

    /**
     * importData
     */
    public function importData($results, $sessionKey = null)
    {
        foreach ($results as $row => $data) {
            $entry = EntryRecord::inSectionUuid($this->blueprint_uuid);

            $id = $data['id'] ?? null;
            if (!$id) {
                $this->logSkipped($row, 'Missing entry ID');
                continue;
            }

            $exists = false;
            if ($record = $entry->find($id)) {
                $entry = $record;
                $exists = true;

                // @todo updating unsupported
                $this->logSkipped($row, 'Entry ID already exists');
                continue;
            }

            foreach ($data as $attr => $value) {
                $this->decodeModelAttribute($entry, $attr, $value, $sessionKey);
            }

            $entry->forceSave(null, $sessionKey);

            if ($exists) {
                $this->logUpdated();
            }
            else {
                $this->logCreated();
            }
        }
    }

    /**
     * decodeModelAttribute
     */
    public function decodeModelAttribute($model, $attr, $value, $sessionKey)
    {
        if ($model->hasRelation($attr)) {
            $relationModel = $model->makeRelation($attr);
            if ($relationModel instanceof RepeaterItem) {
                $this->decodeRepeaterItems($model, $attr, $value, $sessionKey);
            }
            else {
                $model->setRelationValue($attr, $value);
            }
        }
        else {
            $model->$attr = $value;
        }
    }

    /**
     * decodeRepeaterItems
     */
    protected function decodeRepeaterItems($model, $attr, $values, $sessionKey)
    {
        if ($model->isRelationTypeSingular($attr)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            $item = $model->makeRelation($attr);
            $item->content_group = $value['content_group'] ?? null;
            $item->extendWithBlueprint();

            $this->decodeRepeaterItem($item, $value, $sessionKey);

            // Repeaters "has many" relations are without a session key
            // and the saving chain is deferred in memory instead
            $model->$attr()->add($item);
        }
    }

    /**
     * decodeRepeaterItem
     */
    protected function decodeRepeaterItem($model, $data, $sessionKey)
    {
        foreach ($data as $attr => $value) {
            $this->decodeModelAttribute($model, $attr, $value, $sessionKey);
        }
    }
}
