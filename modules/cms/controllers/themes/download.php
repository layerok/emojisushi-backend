<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('cms/themes') ?>"><?= __('Themes') ?></a></li>
        <li><?= e(__($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php if ($this->fatalError): ?>

    <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    <p><a href="<?= Backend::url('cms/themes') ?>" class="btn btn-default"><?= __('Return to Themes List') ?></a></p>

<?php endif ?>
