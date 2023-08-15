<?php

/**
 * This file contains package_quiqqer_products_ajax_products_variant_massProcessing
 */

/**
 * Activate a product
 *
 * @param string|array $productIds - Product-ID
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ExceptionStack;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_variant_massProcessing',
    function ($productIds, $fieldId, $value) {
        $productIds = json_decode($productIds, true);
        $fieldId = (int)$fieldId;
        $value = json_decode($value, true);

        $Exceptions = new ExceptionStack();

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getProduct($productId);
                $Product->getField($fieldId)->setValue($value);
                $Product->save();
            } catch (QUI\Exception $Exception) {
                $Exceptions->addException($Exception);
            }
        }

        if (!$Exceptions->isEmpty()) {
            $messages = array_map(function ($Message) {
                return $Message->getMessage();
            }, $Exceptions->getExceptionList());

            QUI::getMessagesHandler()->addError(implode("<br /><br />", $messages));
            return;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/products', '')
        );
    },
    ['productIds', 'fieldId', 'value'],
    'Permission::checkAdminUser'
);
