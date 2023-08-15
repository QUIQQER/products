<?php

/**
 * Get price fields for price multiplier config
 *
 * @return array - price field data
 */

use QUI\ERP\Products\Handler\Fields;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_getPriceFields',
    function () {
        $fields = Fields::getFields([
            'where' => [
                'type' => [
                    'type' => 'IN',
                    'value' => [
                        'Price',
                        'PriceByTimePeriod',
                        'PriceByQuantity'
                    ]
                ]
            ],
            'order' => 'id ASC'
        ]);

        $priceFields = [];

        foreach ($fields as $Field) {
            $priceFields[] = [
                'id' => $Field->getId(),
                'title' => $Field->getTitle(),
                'edit' => true//$Field->getId() !== Fields::FIELD_PRICE
            ];
        }

        return $priceFields;
    },
    [],
    'Permission::checkAdminUser'
);
