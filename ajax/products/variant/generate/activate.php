<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_generate_activate
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\ProductActivation;

/**
 * Activate a variant list
 *
 * @param string $variantIds - JSON Ids of variants
 * @param integer $activateImages - Set to 1 to also activate inactive variant images
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_activate',
    function ($variantIds, $activateImages = 0) {
        $variantIds = json_decode($variantIds, true);
        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($variantIds as $variantId) {
            try {
                $Variant = Products::getProduct($variantId);
                ProductActivation::activate($Variant, (int)$activateImages === 1);
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            throw new QUI\Exception(
                $ExceptionStack->getMessage(),
                $ExceptionStack->getCode()
            );
        }
    },
    ['variantIds', 'activateImages'],
    'Permission::checkAdminUser'
);
