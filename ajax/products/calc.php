<?php

/**
 * This file contains package_quiqqer_products_ajax_products_calc
 */

use QUI\ERP\Products\Field\CustomCalcFieldInterface;
use QUI\ERP\Products\Handler\Products;

/**
 * Calculate the product price
 *
 * @param integer $productId - Product-ID
 * @param string $fields - JSON fields
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_calc',
    function ($productId, $fields, $quantity) {
        $fields = json_decode($fields, true);
        $Product = Products::getProduct($productId);

        if (!is_array($fields)) {
            $fields = [];
        }

        foreach ($fields as $field) {
            try {
                if (!isset($field['fieldId']) || !isset($field['value'])) {
                    continue;
                }

                $fieldId = $field['fieldId'];
                $fieldValue = $field['value'];

                $Field = $Product->getField($fieldId);

                if (!($Field instanceof CustomCalcFieldInterface)) {
                    continue;
                }

                $Field->setValue($fieldValue);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_WARNING);
            }
        }

        $Unique = $Product->createUniqueProduct(QUI::getUserBySession());
        $Unique->setQuantity(!empty($quantity) ? (float)$quantity : 1);

        return $Unique->getView()->toArray();
    },
    ['productId', 'fields', 'quantity']
);
