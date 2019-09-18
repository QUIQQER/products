<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_generate_delete
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Delete a variant list
 *
 * @param string $variantIds - JSON Ids of variants
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_generate_delete',
    function ($variantIds) {
        $variantIds     = json_decode($variantIds, true);
        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($variantIds as $variantId) {
            try {
                $Variant = Products::getProduct($variantId);
                $Variant->delete();
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
    ['variantIds'],
    'Permission::checkAdminUser'
);
