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
        try {
            $price = QUI\ERP\Money\Price::validatePrice($price);
            $baseFormatted = QUI\ERP\Defaults::getCurrency()->format($price);

            $bruttoPrice = Calc::calcBruttoPrice($price, false, $productId);
            $nettoPriceFormatted = Calc::calcNettoPrice($bruttoPrice, true, $productId);

            if ($baseFormatted === $nettoPriceFormatted) {
                return Calc::calcBruttoPrice($price, $formatted, $productId);
            }

            return Calc::calcBruttoPrice($price, $formatted, $productId);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new \QUI\ERP\Exception(['quiqqer/products', 'ajax.general_error']);
        }
    },
    ['price', 'formatted', 'productId']
);
