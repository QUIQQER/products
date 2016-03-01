<?php

$ProductList = new \QUI\ERP\Products\Controls\Category\ProductList(array(
    'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId')
));

$Engine->assign(array(
    'ProductList' => $ProductList
));
