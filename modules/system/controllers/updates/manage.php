<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('system/updates') ?>"><?= e(__('System Updates')) ?></a></li>
        <li><?= e(__($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<?= $this->listRender('manage') ?>
