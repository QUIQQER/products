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
     * UniqueProductFrontendView constructor.
     *
     * @param int $pid
     * @param array $attributes
     */
    public function __construct($pid, array $attributes)
    {
        parent::__construct($pid, $attributes);

        if (isset($attributes['calculated_basisPrice'])) {
            $this->basisPrice = $attributes['calculated_basisPrice'];
        }

        if (isset($attributes['calculated_price'])) {
            $this->price = $attributes['calculated_price'];
        }

        if (isset($attributes['calculated_sum'])) {
            $this->sum = $attributes['calculated_sum'];
        }

        if (isset($attributes['calculated_nettoSum'])) {
            $this->nettoSum = $attributes['calculated_nettoSum'];
        }

        if (isset($attributes['calculated_isEuVat'])) {
            $this->isEuVat = $attributes['calculated_isEuVat'];
        }

        if (isset($attributes['calculated_isNetto'])) {
            $this->isNetto = $attributes['calculated_isNetto'];
        }

        if (isset($attributes['calculated_vatArray'])) {
            $this->vatArray = $attributes['calculated_vatArray'];
        }

        if (isset($attributes['calculated_factors'])) {
            $this->factors = $attributes['calculated_factors'];
        }

        if (isset($attributes['user_data'])) {
            $this->userData = $attributes['user_data'];
        }
    }

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
