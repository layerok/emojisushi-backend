<?php if (!$this->fatalError): ?>
    <?php Block::put('breadcrumb') ?>
        <ul>
            <li><a href="<?= Backend::url('tailor/entries/'.$activeSource->handleSlug) ?>"><?= $activeSource->name ?></a></li>
            <li><?= e(__($this->pageTitle)) ?></li>
        </ul>
    <?php Block::endPut() ?>

    <?php Block::put('form-contents') ?>

        <?= $this->makePartial('form_history_links') ?>

        <div class="layout-row min-size">
            <?= $this->formRenderOutsideFields() ?>
        </div>

        <div class="layout-row" id="entryPrimaryTabs">
            <?= $this->formRenderPrimaryTabs() ?>
        </div>

        <?= BackendUi::formToolbar(

            BackendUi::ajaxButton()
                ->label('Restore this Version')
                ->ajaxHandler('onRestoreVersion')
                ->loadingMessage(trans('backend::lang.form.saving_name', ['name'=>$entityName]))
                ->primary()

        ) ?>

    <?php Block::endPut() ?>

    <?php Block::put('form-sidebar') ?>
        <div class="hide-tabs"><?= $this->formRenderSecondaryTabs() ?></div>
    <?php Block::endPut() ?>

    <?php Block::put('body') ?>
        <?= Form::open(['class'=>'layout stretch']) ?>
            <?= $this->makeLayout('form-with-sidebar') ?>
        <?= Form::close() ?>
    <?php Block::endPut() ?>

<?php else: ?>

    <p class="flash-message static error"><?= e(__($this->fatalError)) ?></p>

    <p><?= BackendUi::button()->label('Return to Entries')->linkTo('tailor/entries') ?></p>

<?php endif ?>
