<?php

/**
 * This file contains the product-list site type
 *
 * @var QUI\Projects\Project $Project
 * @var QUI\Projects\Site $Site
 * @var QUI\Interfaces\Template\EngineInterface $Engine
 * @var QUI\Template $Template
 **/

use QUI\ERP\Products\Handler\Products;

$productIds = $Site->getAttribute('quiqqer.products.settings.productIds');
$productIds = explode(',', $productIds);

$products = [];

foreach ($productIds as $productId) {
    try {
        $Product = Products::getProduct((int)$productId);
        $products[] = $Product->getView();
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::writeException($Exception);
    }
}

$Engine->assign([
    'products' => $products
]);
