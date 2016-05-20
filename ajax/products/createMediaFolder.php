<?php

/**
 * This file contains package_quiqqer_products_ajax_products_createMediaFolder
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Create the media folder
 *
 * @param integer $productId - Product-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_createMediaFolder',
    function ($productId) {
        $Product = Products::getProduct($productId);
        $Folder  = $Product->createMediaFolder();

        return QUI\Projects\Media\Utils::parseForMediaCenter($Folder);
    },
    array('productId')
);
