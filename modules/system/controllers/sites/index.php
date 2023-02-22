<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('system/settings') ?>"><?= __("Settings") ?></a></li>
        <li><?= e(__($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<?= $this->listRender() ?>
