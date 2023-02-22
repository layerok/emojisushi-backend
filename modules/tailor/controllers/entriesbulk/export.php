<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('tailor/entries/'.$this->activeSource->handleSlug) ?>"><?= $this->activeSource->name ?></a></li>
        <li><?= e(__($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<?= Form::open(['class' => 'layout']) ?>

    <div class="layout-row">
        <?= $this->exportRender() ?>
    </div>

    <div class="form-buttons">
        <div class="loading-indicator-container">
            <button
                type="submit"
                data-control="popup"
                data-handler="onExportLoadForm"
                data-keyboard="false"
                class="btn btn-primary">
                <?= __("Export Entries") ?>
            </button>
        </div>
    </div>

<?= Form::close() ?>
