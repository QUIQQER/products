<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getVariant
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;

/**
 * Return the product variant html
 *
 * @param string $productId - ID of a product
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getVariant',
    function ($productId, $fields) {
        try {
            $Product = Products::getNewProductInstance($productId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return '';
        }

        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($fields as $fieldId => $fieldValue) {
            try {
                $Product->getField($fieldId)->setValue($fieldValue);
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            $list = $ExceptionStack->getExceptionList();

            throw new $list[0];
        }

        // @todo search variant child
        // @todo generate variant hash

        $Control = new ProductControl([
            'Product' => $Product
        ]);

        return $Control->create();
    },
    ['productId', 'fields']
);
