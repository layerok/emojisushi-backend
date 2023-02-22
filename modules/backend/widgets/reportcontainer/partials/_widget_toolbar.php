<div class="dropdown dropup">
    <a
        href="javascript:;"
        class="manage-widgets"
        data-toggle="dropdown">
        <i class="icon-cogs"></i> <?= e(trans('backend::lang.dashboard.manage_widgets')) ?>
    </a>

    <ul class="dropdown-menu" role="menu">
        <li role="presentation">
            <a
                role="menuitem"
                href="javascript:;"
                class="dropdown-item"
                data-control="popup"
                data-handler="<?= $this->getEventHandler('onLoadAddPopup') ?>"
                tabindex="-1">
                <i class="icon-plus"></i>
                <?= e(trans('backend::lang.dashboard.add_widget')) ?>
            </a>
        </li>
        <li role="separator" class="dropdown-divider"></li>
        <?php if (BackendAuth::userHasAccess('dashboard.defaults')): ?>
            <li role="presentation">
                <a
                    role="menuitem"
                    href="javascript:;"
                    class="dropdown-item"
                    data-request="<?= $this->getEventHandler('onMakeLayoutDefault') ?>"
                    data-request-confirm="<?= e(trans('backend::lang.dashboard.make_default_confirm')) ?>"
                    tabindex="-1">
                    <i class="icon-floppy-o"></i>
                    <?= e(trans('backend::lang.dashboard.make_default')) ?>
                </a>
            </li>
        <?php endif ?>
        <li role="presentation">
            <a
                role="menuitem"
                href="javascript:;"
                class="dropdown-item"
                data-request-success="$(window).trigger('oc.reportWidgetRefresh')"
                data-request="<?= $this->getEventHandler('onResetWidgets') ?>"
                data-request-confirm="<?= e(trans('backend::lang.dashboard.reset_layout_confirm')) ?>"
                tabindex="-1">
                <i class="icon-repeat"></i>
                <?= e(trans('backend::lang.dashboard.reset_layout')) ?>
            </a>
        </li>
    </ul>
</div>
