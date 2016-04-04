<?php

/**
 * This file contains package_quiqqer_products_ajax_products_calc
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Fields;

/**
 * Calculate the product price
 *
 * @param integer $productId - Product-ID
 * @param string $fields - JSON fields
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_calc',
    function ($productId, $fields) {
        $fields  = json_decode($fields, true);
        $Product = Products::getProduct($productId);

        foreach ($fields as $fieldId => $value) {
            try {
                $Field = Fields::getField($fieldId);
                $Field->setValue($value);

                $Product->addField($Field);

            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, \QUI\System\Log::LEVEL_WARNING);
            }
        }

        return \QUI\ERP\Products\Utils\Calc::getProductPrice($Product);
    },
    array('productId', 'fields')
);
