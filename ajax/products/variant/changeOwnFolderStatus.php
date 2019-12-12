<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_changeOwnFolderStatus
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 *
 * @param integer $productId - Product-ID
 * @param string $options - JSON Array - Grid options
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_changeOwnFolderStatus',
    function ($productId) {
        $Product = Products::getProduct($productId);

        if (!($Product instanceof QUI\ERP\Products\Product\Types\VariantChild)) {
            return;
        }

        if (!$Product->hasOwnMediaFolder()) {
            $Product->createOwnMediaFolder();
        }
    },
    ['productId'],
    'Permission::checkAdminUser'
);
