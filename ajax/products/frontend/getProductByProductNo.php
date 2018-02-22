<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProduct
 */

use QUI\ERP\Products\Product\Product;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;

/**
 * Return the product html
 *
 * @param string $productId - ID of a product
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getProductByProductNo',
    function ($productNo) {
        $Product = QUI\ERP\Products\Handler\Products::getProductByProductNo(
            $productNo
        );

        return $Product->getView()->getAttributes();
    },
    array('productNo')
);
