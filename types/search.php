<?php

use \QUI\ERP\Products\Utils\Search as SearchUtils;
use \QUI\ERP\Products\Controls\Category\ProductList;

$ProductList = new ProductList(array(
    'categoryId'   => $Site->getAttribute('quiqqer.products.settings.categoryId'),
    'autoload'     => false,
    'searchParams' => SearchUtils::getSearchParameterFromRequest(),
    'view'         => SearchUtils::getViewParameterFromRequest()
));

if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
    $ProductList->setAttribute('showFilter', false);
}

$Engine->assign(array(
    'ProductList' => $ProductList
));
