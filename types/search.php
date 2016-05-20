<?php

use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Controls\Search\Search;

$Search = new Search(array(
    'Site' => $Site,
    'data-name' => 'search'
));

$ProductList = new ProductList(array(
    'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId'),
    'Search' => $Search
));

$Engine->assign(array(
    'ProductList' => $ProductList,
    'Search' => $Search
));
