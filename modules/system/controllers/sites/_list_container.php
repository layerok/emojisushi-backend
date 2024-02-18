<?php if ($toolbar): ?>
    <?= $toolbar->render() ?>
<?php endif ?>

<?php if (!$useGroups): ?>
    <div class="list-widget-container">
        <?php if ($filter): ?>
            <?= $filter->render() ?>
        <?php endif ?>

        <?= $list->render() ?>
    </div>
<?php else: ?>
    <div class="ps-md-4">
        <div class="row gx-0">
            <div class="col-md-3 col-lg-2 mb-3" id="<?= $this->getId('listTabs') ?>">
                <?= $this->makePartial('list_tabs') ?>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="layout-row">
                    <div class="list-widget-container">
                        <?php if ($filter): ?>
                            <?= $filter->render() ?>
                        <?php endif ?>

                        <?= $list->render() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
