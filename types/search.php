<?php

use \QUI\ERP\Products\Utils\Search as SearchUtils;
use \QUI\ERP\Products\Controls\Category\ProductList;

$ProductList = new ProductList([
    'categoryId'   => $Site->getAttribute('quiqqer.products.settings.categoryId'),
    'autoload'     => false,
    'searchParams' => SearchUtils::getSearchParameterFromRequest(),
    'view'         => SearchUtils::getViewParameterFromRequest()
]);

if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
    $ProductList->setAttribute('showFilter', false);
}

$ProductList->addSort(
    QUI::getLocale()->get('quiqqer/products', 'sort.cdate.ASC'),
    'c_date ASC'
);

$ProductList->addSort(
    QUI::getLocale()->get('quiqqer/products', 'sort.cdate.DESC'),
    'c_date DESC'
);

$Engine->assign([
    'ProductList' => $ProductList
]);
