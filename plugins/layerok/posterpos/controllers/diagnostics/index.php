
<h2>Товари, які відсутні в постері:</h2>
<?php if(count($stale_products) > 0): ?>
    <ul>
        <?php foreach($stale_products as $stale_product): ?>
                <li><?= $stale_product->poster_id ?> - <?= $stale_product->name ?></li>
        <?php endforeach; ?>
    </ul>
<?php  else: ?>
    <p>Таких товарів немає</p>
<?php endif; ?>

<h2>Товари, які відсутні на сайті:</h2>
<?php if(count($missing_products) > 0): ?>
    <ul>
        <?php foreach($missing_products as $missing_product): ?>
            <li>
                <span><?= $missing_product->product_id ?> - <?= $missing_product->product_name ?></span>
                <a href="/backend/layerok/posterpos/diagnostics/add?poster_id=<?= $missing_product->product_id ?>">Додати на сайт</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php  else: ?>
    <p>Таких товарів немає</p>
<?php endif; ?>

<h2>Товари, які не прив'язані до постера:</h2>
<?php if(count($disconnected_products) > 0): ?>
    <ul>
        <?php foreach($disconnected_products as $disconnected_product): ?>
            <li><?= $disconnected_product->name ?></li>
        <?php endforeach; ?>
    </ul>
<?php  else: ?>
    <p>Таких товарів немає</p>
<?php endif; ?>

