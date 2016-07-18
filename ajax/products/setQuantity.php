<?php

/**
 * This file contains package_quiqqer_products_ajax_products_setQuantity
 */
use QUI\ERP\Products\Handler\Products;

/**
 * Set the quantity of an product
 *
 * @param integer $productId
 * @param integer|float $quantity
 * @return integer|float
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_setQuantity',
    function ($productId, $quantity) {
        $Product = Products::getProduct($productId);
        $Unique  = $Product->createUniqueProduct(QUI::getUserBySession());
        $Unique->setQuantity($quantity);

        return $Unique->getQuantity();
    },
    array('productId', 'quantity')
);
