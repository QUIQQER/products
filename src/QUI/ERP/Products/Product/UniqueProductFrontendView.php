<?php

/**
 * This file contains QUI\ERP\Products\Product\UniqueProductFrontendView
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\Exception;

/**
 * Class UniqueProductFrontendView
 *
 * @event onQuiqqerProductsPriceFactorsInit [
 *      QUI\ERP\Products\Utils\PriceFactors,
 *      QUI\ERP\Products\Interfaces\ProductInterface
 * ]
 */
class UniqueProductFrontendView extends UniqueProduct
{
    /**
     * @var bool|mixed
     */
    protected mixed $hasOfferPrice = false;

    /**
     * @var float|bool
     */
    protected mixed $originalPrice = false;

    /**
     * UniqueProductFrontendView constructor.
     *
     * @param int $pid
     * @param array $attributes
     *
     * @throws QUI\Exception
     */
    public function __construct(int $pid, array $attributes)
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

        if (isset($attributes['hasOfferPrice'])) {
            $this->hasOfferPrice = $attributes['hasOfferPrice'];
        }

        if (isset($attributes['originalPrice'])) {
            $this->originalPrice = $attributes['originalPrice'];
        }
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Money\Price
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        try {
            return parent::getPrice();
        } catch (QUI\Exception) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Money\Price
     */
    public function getUnitPrice(): QUI\ERP\Money\Price
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        try {
            return parent::getUnitPrice();
        } catch (QUI\Exception) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }
    }

    /**
     * Has the product an offer price?
     *
     * @return bool
     */
    public function hasOfferPrice(): bool
    {
        return $this->hasOfferPrice;
    }

    /**
     * Return the original price if an offer prices exists
     *
     * @return QUI\ERP\Money\Price
     * @throws Exception
     */
    public function getOriginalPrice(): QUI\ERP\Money\Price
    {
        if ($this->originalPrice) {
            return new QUI\ERP\Money\Price(
                $this->originalPrice,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return self::getPrice();
    }

    /**
     * Return the netto price of the product
     *
     * @return QUI\ERP\Money\Price
     */
    public function getNettoPrice(): QUI\ERP\Money\Price
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        try {
            return parent::getNettoPrice();
        } catch (QUI\Exception) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }
    }

    /**
     * Return the product attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $attributes['calculated_basisPrice'] = '';
            $attributes['calculated_price'] = '';
            $attributes['calculated_sum'] = '';
            $attributes['calculated_nettoSum'] = '';
        }

        return $attributes;
    }
}
