<?php

use QUI\ERP\Products\Controls\Category\ProductList;

$ProductList = new ProductList(array(
    'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId'),
    'autoload'   => false
));

if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
    $ProductList->setAttribute('showFilter', false);
}

$Engine->assign(array(
    'ProductList' => $ProductList
));
