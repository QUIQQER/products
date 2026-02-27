<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_hasOwnFolder
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;

/**
 * Has the variant its own media folder
 *
 * @param integer $productId - Product-ID
 * @return bool
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_variant_hasOwnFolder',
    function ($productId) {
        /* @var $Product VariantChild */
        $Product = Products::getProduct($productId);

        if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            return $Product->hasOwnMediaFolder();
        }

        return true;
    },
    ['productId'],
    'Permission::checkAdminUser'
);
