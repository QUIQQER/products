<?php

/**
 * This file contains package_quiqqer_products_ajax_products_calcBruttoPrice
 */

use QUI\ERP\Products\Utils\Calc;

/**
 * Calculate the product brutto price
 *
 * @param integer|float $price - Price to calc (netto price)
 * @param bool $formatted - output formatted?
 * @param integer $productId - optional
 *
 * @return float
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_calcBruttoPrice',
    function ($price, $formatted, $productId) {
        $price         = QUI\ERP\Money\Price::validatePrice($price);
        $baseFormatted = QUI\ERP\Defaults::getCurrency()->format($price);

        $bruttoPrice         = Calc::calcBruttoPrice($price, false, $productId);
        $nettoPriceFormatted = Calc::calcNettoPrice($bruttoPrice, true, $productId);

        if ($baseFormatted === $nettoPriceFormatted) {
            return Calc::calcBruttoPrice($price, $formatted, $productId);
        }

        // @todo +1 -1 cent

        return Calc::calcBruttoPrice($price, $formatted, $productId);
    },
    ['price', 'formatted', 'productId']
);
