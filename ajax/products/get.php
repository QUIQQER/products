<?php

/**
 * This file contains package_quiqqer_products_ajax_products_get
 */

/**
 * Returns a product
 *
 * @param string $productId - Product-ID
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_get',
    function ($productId) {
        try {
            $Products = new QUI\ERP\Products\Handler\Products();
            $Product  = $Products->getProduct($productId);

            if (QUI::isFrontend()) {
                return $Product->getView()->getAttributes();
            }

            $attributes = $Product->getAttributes();

            $attributes['typeTitle']        = $Product->getTypeTitle();
            $attributes['typeDescription']  = $Product->getTypeDescription();
            $attributes['typeIsSelectable'] = $Product->isTypeSelectable();
            $attributes['typePanel']        = $Product->getTypeBackendPanel();

            foreach ($attributes['fields'] as $key => $field) {
                $attributes['fields'][$key]['source'] = $Product->getFieldSource($field['id']);
            }

            return $attributes;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw $Exception;
        }
    },
    ['productId']
);
