<?php foreach (BackendMenu::listMainMenuItemsWithSubitems() as $itemIndex => $itemInfo): ?>
    <?php
        $item = $itemInfo->mainMenuItem;
        $isActive = BackendMenu::isMainMenuItemActive($item);
    ?>
    <li
        class="svg-icon-container svg-active-effects mainmenu-item <?= $isActive ? 'active' : '' ?> <?= $itemInfo->subMenuHasDropdown ? 'has-subitems' : '' ?>"
        data-submenu-index="<?= $itemIndex ?>">
        <a href="<?= $item->url ?>">
            <?= $this->makeLayoutPartial('mainmenu_item', ['item' => $item]) ?>
        </a>
    </li>
<?php endforeach ?>
