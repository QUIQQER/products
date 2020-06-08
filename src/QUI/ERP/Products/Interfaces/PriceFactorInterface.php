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
    public function getIdentifier();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @return int
     */
    public function getCalculation();

    /**
     * @return int
     */
    public function getCalculationBasis();

    /**
     * @return integer|float|double
     */
    public function getValue();

    /**
     * @return string
     */
    public function getValueText();

    /**
     * Is the price factor visible
     *
     * @return boolean
     */
    public function isVisible();

    /**
     * Set the title
     *
     * @param string $title
     */
    public function setTitle($title);

    /**
     * Set the title
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * The the value for the calculation
     * 10 -> 10%, 10€, 10$
     *
     * @param integer|float|double $value - value to calc
     */
    public function setValue($value);

    /**
     * The text for the value presentation
     * If no value text is set, normal value is used
     *
     * @param string $value
     */
    public function setValueText($value);

    /**
     * Set the priority of the calculation
     *
     * @param int $priority
     */
    public function setPriority($priority);

    /**
     * Set the calculation type / method
     *
     * @param int $calculation - Calc::CALCULATION_COMPLEMENT, Calc::CALCULATION_PERCENTAGE
     */
    public function setCalculation($calculation);

    /**
     * Set the calculation basis
     * Calculation from the netto price of a product or
     * the current price of a product in the calculation process
     *
     * @param int $basis -  Calc::CALCULATION_BASIS_NETTO, Calc::CALCULATION_BASIS_CURRENTPRICE
     */
    public function setCalculationBasis($basis);

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
    public function getNettoSumFormatted();

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
     * @return string
     */
    public function getSumFormatted();

    /**
     * Returns the price factor as an array
     *
     * @return array
     */
    public function toArray();

    /**
     * @return QUI\ERP\Accounting\PriceFactors\Factor
     */
    public function toErpPriceFactor();
}
