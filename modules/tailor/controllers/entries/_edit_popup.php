<?php
    $model = $relationManageWidget->getModel();
    if (!$relationManageId) {
        $model->setDefaultContentGroup($model->exists ? post('EntryRecord[content_group]') : null);
    }
    $initialState = $this->makeInitialState($model);
    $langState = $this->makeLangState();
?>
<div
    id="<?= $relationManageWidget->getId('managePopup') ?>"
    class="tailor-entry-edit-popup"
    data-control="vue-app">
    <?php if ($relationManageId): ?>

        <?= Form::ajax('onRelationManageUpdate', [
            'sessionKey' => $newSessionKey,
            'data-popup-load-indicator' => true,
            'data-request-success' => "oc.relationBehavior.changed('" . e($relationField) . "', 'updated')",
        ]) ?>

            <!-- Passable fields -->
            <input type="hidden" name="manage_id" value="<?= $relationManageId ?>" />
            <input type="hidden" name="_relation_field" value="<?= $relationField ?>" />
            <input type="hidden" name="_relation_mode" value="form" />
            <input type="hidden" name="_relation_session_key" value="<?= $relationSessionKey ?>" />
            <input type="hidden" name="EntryRecord[content_group]" value="<?= e($formModel->content_group) ?>"/>

            <div class="modal-header">
                <div class="flex-grow-1">
                    <h4 class="modal-title"><?= e($relationManageTitle) ?></h4>
                </div>
                <div class="me-3">
                    <?= $this->makePartial('edit_header_controls', ['initialState' => $initialState]) ?>
                </div>
                <button type="button" class="btn-close" data-dismiss="popup"></button>
            </div>

            <div class="modal-body">
                <?= $relationManageWidget->render(['preview' => $this->readOnly]) ?>
            </div>

            <div class="modal-footer">
                <?php if ($this->readOnly): ?>
                    <button
                        type="button"
                        class="btn btn-default"
                        data-dismiss="popup">
                        <?= e(trans('backend::lang.relation.close')) ?>
                    </button>
                <?php else: ?>
                    <button
                        type="submit"
                        class="btn btn-primary">
                        <?= e(trans('backend::lang.relation.update')) ?>
                    </button>
                    <button
                        type="button"
                        class="btn btn-default"
                        data-dismiss="popup">
                        <?= e(trans('backend::lang.relation.cancel')) ?>
                    </button>
                <?php endif ?>
            </div>

        <?= Form::close() ?>

    <?php else: ?>

        <?= Form::ajax('onRelationManageCreate', [
            'sessionKey' => $newSessionKey,
            'data-popup-load-indicator' => true,
            'data-request-success' => "oc.relationBehavior.changed('" . e($relationField) . "', 'created')",
        ]) ?>

            <!-- Passable fields -->
            <input type="hidden" name="_relation_field" value="<?= $relationField ?>" />
            <input type="hidden" name="_relation_mode" value="form" />
            <input type="hidden" name="_relation_session_key" value="<?= $relationSessionKey ?>" />
            <input type="hidden" name="EntryRecord[content_group]" value="<?= e($formModel->content_group) ?>"/>

            <div class="modal-header">
                <div class="flex-grow-1">
                    <h4 class="modal-title"><?= e($relationManageTitle) ?></h4>
                </div>
                <div class="me-3">
                    <?= $this->makePartial('edit_header_controls', ['initialState' => $initialState]) ?>
                </div>
                <button type="button" class="btn-close" data-dismiss="popup"></button>
            </div>

            <div class="modal-body">
                <?= $relationManageWidget->render() ?>
            </div>

            <div class="modal-footer">
                <button
                    type="submit"
                    class="btn btn-primary">
                    <?= e(trans('backend::lang.relation.create')) ?>
                </button>
                <button
                    type="button"
                    class="btn btn-default"
                    data-dismiss="popup">
                    <?= e(trans('backend::lang.relation.cancel')) ?>
                </button>
            </div>
        <?= Form::close() ?>

    <?php endif ?>

    <script type="text/template" data-vue-state="initial"><?= json_encode($initialState) ?></script>
    <script type="text/template" data-vue-lang><?= json_encode($langState) ?></script>
</div>

<script>
    oc.relationBehavior.bindToPopups('#<?= $relationManageWidget->getId("managePopup") ?>', {
        _relation_field: '<?= $relationField ?>',
        _relation_mode: 'form'
    });
</script>

<style>
    .tailor-entry-edit-popup .control-tabs.secondary-tabs {
        display: none;
    }
</style>
