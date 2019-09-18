<?php

/**
 * This file contains package_quiqqer_products_ajax_products_calcBruttoPrice
 */

use QUI\ERP\Tax\TaxEntry;
use QUI\ERP\Tax\TaxType;
use QUI\ERP\Tax\Utils as TaxUtils;

/**
 * Calculate the product brutto price
 *
 * @param integer|float $price - Price to calc
 * @return float
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_calcBruttoPrice',
    function ($price, $formatted) {
        $price   = QUI\ERP\Money\Price::validatePrice($price);
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
        $vat = (100 + $vat) / 100;

        $price = $price * $vat;

        if (isset($formatted) && $formatted) {
            return QUI\ERP\Defaults::getCurrency()->format($price);
        }

        return $price;
    },
    ['price', 'formatted']
);
