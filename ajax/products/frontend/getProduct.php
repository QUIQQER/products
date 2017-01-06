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
    'package_quiqqer_products_ajax_products_frontend_getProduct',
    function ($productId) {
        try {
            $Product = new Product($productId);
            $View    = $Product->getView();

            $Control = new ProductControl(array(
                'Product' => $View
            ));

            $control = $Control->create();

            return array(
                'css'  => QUI\Control\Manager::getCSS(),
                'html' => $control
            );
        } catch (QUI\Exception $Exception) {
        }

        return '';
    },
    array('productId')
);
