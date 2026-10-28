<?php

/**
 * This file contains package_quiqqer_products_ajax_products_copy
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\ProductMediaCopy;

/**
 * Create a new product
 *
 * @param string|integer $productId - Product ID
 * @param string|null $mediaMode - copy, share or empty (legacy default)
 * @return integer - new product id
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_copy',
    function ($productId, $mediaMode = null) {
        return Products::copyProduct((int)$productId, $mediaMode ?? ProductMediaCopy::EMPTY)->getId();
    },
    ['productId', 'mediaMode'],
    'Permission::checkAdminUser'
);
