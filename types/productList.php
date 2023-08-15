<?php

use QUI\ERP\Products\Handler\Products;

$productIds = $Site->getAttribute('quiqqer.products.settings.productIds');
$productIds = \explode(',', $productIds);

$products = [];

foreach ($productIds as $productId) {
    try {
        $Product = Products::getProduct($productId);
        $products[] = $Product->getView();
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::writeException($Exception);
    }
}

$Engine->assign([
    'products' => $products
]);
