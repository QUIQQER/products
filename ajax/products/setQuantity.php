<?php

/**
 * This file contains package_quiqqer_products_ajax_products_setQuantity
 */

use QUI\ERP\Products\Field\Types\BasketConditions;
use QUI\ERP\Products\Handler\Products;

/**
 * Set the quantity of a product
 *
 * @param integer $productId
 * @param integer|float $quantity
 * @return integer|float
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_setQuantity',
    function ($productId, $quantity) {
        $Product = Products::getProduct($productId);

        // check basket conditions
        $condition = QUI\ERP\Products\Utils\Products::getBasketCondition($Product);

        if ($condition === BasketConditions::TYPE_2 ||
            $condition === BasketConditions::TYPE_3 ||
            $condition === BasketConditions::TYPE_5
        ) {
            $quantity = 1;
        }

        $Unique = $Product->createUniqueProduct(QUI::getUserBySession());
        $Unique->setQuantity($quantity);

        return $Unique->getQuantity();
    },
    ['productId', 'quantity']
);
