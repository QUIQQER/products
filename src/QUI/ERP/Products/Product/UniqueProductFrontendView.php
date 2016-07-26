<?php

/**
 * This file contains QUI\ERP\Products\Product\UniqueProductFrontendView
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class UniqueProductFrontendView
 *
 * @event onQuiqqerProductsPriceFactorsInit [QUI\ERP\Products\Utils\PriceFactors, QUI\ERP\Products\Interfaces\Product]
 */
class UniqueProductFrontendView extends UniqueProduct
{
    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Products\Utils\Price(
                '',
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return parent::getPrice();
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getUnitPrice()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Products\Utils\Price(
                '',
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return parent::getUnitPrice();
    }

    /**
     * Return the netto price of the product
     *
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getNettoPrice()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Products\Utils\Price(
                '',
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return parent::getNettoPrice();
    }

    /**
     * Return the product attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $attributes['calculated_basisPrice'] = '';
            $attributes['calculated_price']      = '';
            $attributes['calculated_sum']        = '';
            $attributes['calculated_nettoSum']   = '';
        }

        return $attributes;
    }
}
