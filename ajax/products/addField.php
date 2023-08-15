<?php

/**
 * This file contains package_quiqqer_products_ajax_products_addField
 */

/**
 * Add a field to a product
 * - change the field to an own field
 *
 * @param integer $productId - product-ID
 * @param integer $fieldId - Field-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_addField',
    function ($productId, $fieldId) {
        $Products = new QUI\ERP\Products\Handler\Products();
        $Fields = new QUI\ERP\Products\Handler\Fields();

        $Product = $Products->getProduct($productId);
        $fieldId = \json_decode($fieldId, true);

        if (\is_numeric($fieldId)) {
            $fieldId = [$fieldId];
        }

        try {
            if (\is_array($fieldId)) {
                foreach ($fieldId as $fid) {
                    $Field = $Fields->getField($fid);
                    $Product->addOwnField($Field);
                }
            }

            $Product->save();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    },
    ['productId', 'fieldId'],
    'Permission::checkAdminUser'
);
