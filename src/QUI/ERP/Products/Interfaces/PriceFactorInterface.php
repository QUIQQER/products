<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\PriceFactorInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI;

/**
 * Interface PriceFactor
 *
 * @package QUI\ERP\Products\Interfaces
 */
interface PriceFactorInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @return int
     */
    public function getCalculation(): int;

    /**
     * @return int
     */
    public function getCalculationBasis(): int;

    /**
     * @return integer|float
     */
    public function getValue();

    /**
     * @return string
     */
    public function getValueText(): string;

    /**
     * Is the price factor visible
     *
     * @return boolean
     */
    public function isVisible(): bool;

    /**
     * Set the title
     *
     * @param string $title
     */
    public function setTitle(string $title);

    /**
     * Set the title
     *
     * @param string $description
     */
    public function setDescription(string $description);

    /**
     * The the value for the calculation
     * 10 -> 10%, 10€, 10$
     *
     * @param integer|float $value - value to calc
     */
    public function setValue($value);

    /**
     * The text for the value presentation
     * If no value text is set, normal value is used
     *
     * @param string $value
     */
    public function setValueText(string $value);

    /**
     * Set the priority of the calculation
     *
     * @param int $priority
     */
    public function setPriority(int $priority);

    /**
     * Set the calculation type / method
     *
     * @param int $calculation - Calc::CALCULATION_COMPLEMENT, Calc::CALCULATION_PERCENTAGE
     */
    public function setCalculation(int $calculation);

    /**
     * Set the calculation basis
     * Calculation from the netto price of a product or
     * the current price of a product in the calculation process
     *
     * @param int $basis -  Calc::CALCULATION_BASIS_NETTO, Calc::CALCULATION_BASIS_CURRENTPRICE
     */
    public function setCalculationBasis(int $basis);

    /**
     * Set the netto sum
     *
     * @param int|double|float $sum - sum
     */
    public function setNettoSum($sum);

    /**
     * @return bool|int|float|double
     */
    public function getNettoSum();

    /**
     * @return string
     */
    public function getNettoSumFormatted(): string;

    /**
     * Set the calculated sum
     *
     * @param int|double|float $sum - sum
     */
    public function setSum($sum);

    /**
     * @return bool|int|float|double
     */
    public function getSum();

    /**
     * @param string $currencyCode
     */
    public function setCurrency(string $currencyCode);

    /**
     * @return QUI\ERP\Currency\Currency
     */
    public function getCurrency(): QUI\ERP\Currency\Currency;

    /**
     * @return string
     */
    public function getSumFormatted(): string;

    /**
     * Returns the price factor as an array
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * @return QUI\ERP\Accounting\PriceFactors\Factor
     */
    public function toErpPriceFactor(): QUI\ERP\Accounting\PriceFactors\Factor;
}
