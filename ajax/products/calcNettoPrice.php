<?php

/**
 * This file contains package_quiqqer_products_ajax_products_calcNettoPrice
 */

use QUI\ERP\Products\Utils\Calc;

/**
 * Calculate the netto price
 *
 * @param integer|float $price - Price to calc (brutto price)
 * @param bool $formatted - output formatted?
 * @param integer $productId - optional
 *
 * @return float
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_calcNettoPrice',
    function ($price, $formatted, $productId) {
        $price         = QUI\ERP\Money\Price::validatePrice($price, QUI::getUserBySession()->getLocale());
        $baseFormatted = QUI\ERP\Defaults::getCurrency()->format($price);

        $nettoPrice           = Calc::calcNettoPrice($price, false, $productId);
        $nettoPriceFormatted  = Calc::calcNettoPrice($price, true, $productId);
        $bruttoPriceFormatted = Calc::calcBruttoPrice(
            \floatval($nettoPriceFormatted),
            true,
            $productId
        );
        \QUI\System\Log::writeRecursive($price, \QUI\System\Log::LEVEL_ERROR);
        if ($baseFormatted === $bruttoPriceFormatted) {
            return Calc::calcNettoPrice($price, $formatted, $productId);
        }

        // @todo +1 -1 cent

        return Calc::calcNettoPrice($price, $formatted, $productId);
    },
    ['price', 'formatted', 'productId']
);
