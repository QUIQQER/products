<?php

/**
 * This file contains package_quiqqer_products_ajax_products_calcNettoPrice
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Tax\TaxEntry;
use QUI\ERP\Tax\TaxType;
use QUI\ERP\Tax\Utils as TaxUtils;

/**
 * Calculate the product price
 *
 * @param integer|float $price - Price to calc
 * @return float
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_calcNettoPrice',
    function ($price) {
        $price   = \floatval($price);
        $Area    = QUI\ERP\Defaults::getArea();
        $TaxType = TaxUtils::getTaxTypeByArea($Area);

        if ($TaxType instanceof TaxType) {
            $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
        } elseif ($TaxType instanceof TaxEntry) {
            $TaxEntry = $TaxType;
        } else {
            return $price;
        }

        $vat = $TaxEntry->getValue();
        $vat = ($vat / 100) + 1;

        $price = $price / $vat;

        return $price;
    },
    ['price']
);
