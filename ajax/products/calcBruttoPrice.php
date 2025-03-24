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
    function ($price, $formatted, $productId, $currency) {
        $Currency = QUI\ERP\Defaults::getCurrency();

        if (!empty($currency)) {
            try {
                $Currency = QUI\ERP\Currency\Handler::getCurrency($currency);
            } catch (QUI\Exception) {
                $Currency = QUI\ERP\Defaults::getCurrency();
            }
        }

        try {
            $price = QUI\ERP\Money\Price::validatePrice($price);
            $priceResult = Calc::calcBruttoPrice($price, false, $productId);

            if ($formatted) {
                return $Currency->format($priceResult);
            }

            return $priceResult;
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\ERP\Exception(['quiqqer/products', 'ajax.general_error']);
        }
    },
    ['price', 'formatted', 'productId', 'currency']
);
