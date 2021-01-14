<?php

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Field\Types\Price;
use QUI\ERP\Products\Field\Types\PriceByQuantity;
use QUI\ERP\Products\Field\Types\PriceByTimePeriod;

/**
 * Get price fields for price multiplier config
 *
 * @return array - price field data
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_getPriceFields',
    function () {
        $fields = Fields::getFields([
            'where' => [
                'type' => [
                    'type'  => 'IN',
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
                'id'    => $Field->getId(),
                'title' => $Field->getTitle(),
                'edit'  => true//$Field->getId() !== Fields::FIELD_PRICE
            ];
        }

        return $priceFields;
    },
    [],
    'Permission::checkAdminUser'
);
