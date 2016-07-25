<?php

/**
 * This file contains QUI\ERP\Products\Utils
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Currency\Handler as Currencies;

/**
 * Class PriceFactors
 *
 * PriceFactor is a entry in the PriceFactors List
 * Its a helper to manipulate prices in products
 *
 * @package QUI\ERP\Products\Utils
 */
class PriceFactor implements QUI\ERP\Products\Interfaces\PriceFactor
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var int
     */
    protected $priority = 0;

    /**
     * Value for the calculation
     *
     * @var integer|float|double
     */
    protected $value = 0;

    /**
     * @var bool|integer|double|float
     */
    protected $nettoSum = false;

    /**
     * @var bool
     */
    protected $bruttoSum = false;

    /**
     * @var bool|integer|double|float
     */
    protected $calculatedSum = false;

    /**
     * @var string
     */
    protected $valueText = false;

    /**
     * Is the pricefactor visible
     *
     * @var bool
     */
    protected $visible = true;

    /**
     * @var string
     */
    protected $type = PriceFactors::DEFAULT_TYPE;

    /**
     * Basis calculation
     * of which calculation basis should be calculated
     *
     * @var int
     */
    protected $basis = Calc::CALCULATION_BASIS_NETTO;

    /**
     * Percent or complement?
     * @var int
     */
    protected $calculation = Calc::CALCULATION_COMPLEMENT;

    /**
     * PriceFactor constructor.
     *
     * @param array $params - array(
     *      'title' => '',
     *      'description' => '',
     *      'priority' => '',
     *      'calculation' => '',
     *      'basis' => '',
     *      'value' => '',
     *      'valueText' => '',
     *      'visible' => true
     * )
     */
    public function __construct($params = array())
    {
        if (isset($params['title'])) {
            $this->setTitle($params['title']);
        }

        if (isset($params['description'])) {
            $this->setDescription($params['description']);
        }

        if (isset($params['priority'])) {
            $this->setPriority((int)$params['priority']);
        }

        if (isset($params['calculation'])) {
            $this->setCalculation($params['calculation']);
        }

        if (isset($params['basis'])) {
            $this->setCalculationBasis($params['basis']);
        }

        if (isset($params['value'])) {
            $this->setValue($params['value']);
        }

        if (isset($params['valueText'])) {
            $this->setValueText($params['valueText']);
        }

        if (isset($params['visible'])) {
            if (is_bool($params['visible'])) {
                $this->visible = (bool)$params['visible'];
            } else {
                $this->visible = $params['visible'] ? true : false;
            }
        }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return int
     */
    public function getCalculation()
    {
        return (int)$this->calculation;
    }

    /**
     * @return int
     */
    public function getCalculationBasis()
    {
        return $this->basis;
    }

    /**
     * @return integer|float|double
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getValueText()
    {
        // empty value = no value is set
        if ($this->valueText === '') {
            return '-';
        }

        if (!empty($this->valueText)) {
            return $this->valueText;
        }

        return $this->getBruttoSumFormatted();
    }

    /**
     * Is the price factor visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * Set the title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        if (is_string($title)) {
            $this->title = $title;
        }
    }

    /**
     * Set the title
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        if (is_string($description)) {
            $this->description = $description;
        }
    }

    /**
     * The the value for the calculation
     * 10 -> 10%, 10€, 10$
     *
     * @param integer|float|double $value - value to calc
     */
    public function setValue($value)
    {
        if (is_numeric($value)) {
            $this->value = $value;
        }
    }

    /**
     * The text for the value presentation
     * If no value text is set, normal value is used
     *
     * @param string $value
     */
    public function setValueText($value)
    {
        $this->valueText = $value;
    }

    /**
     * Set the priority of the calculation
     *
     * @param int $priority
     */
    public function setPriority($priority)
    {
        if (is_int($priority)) {
            $this->priority = $priority;
        }
    }

    /**
     * Set the calculation type / method
     *
     * @param int $calculation - Calc::CALCULATION_COMPLEMENT, Calc::CALCULATION_PERCENTAGE
     */
    public function setCalculation($calculation)
    {
        $calculation = (int)$calculation;

        switch ($calculation) {
            case Calc::CALCULATION_COMPLEMENT:
            case Calc::CALCULATION_PERCENTAGE:
                $this->calculation = $calculation;
                break;
        }
    }

    /**
     * Set the calculation basis
     * Calculation from the netto price of a product or
     * the current price of a product in the calculation process
     *
     * @param int $basis -  Calc::CALCULATION_BASIS_NETTO, Calc::CALCULATION_BASIS_CURRENTPRICE
     */
    public function setCalculationBasis($basis)
    {
        switch ($basis) {
            case Calc::CALCULATION_BASIS_NETTO:
            case Calc::CALCULATION_BASIS_CURRENTPRICE:
                $this->basis = $basis;
                break;
        }
    }

    /**
     * Sum method
     */

    /**
     * Set the calculated sum (brutto)
     *
     * @param Calc $Calc - calculation object
     * @param int|double|float $sum - sum
     */
    public function setNettoSum(Calc $Calc, $sum)
    {
        if (is_numeric($sum)) {
            $this->nettoSum = $sum;
        }
    }

    /**
     * @return bool|int|float|double
     */
    public function getNettoSum()
    {
        return $this->nettoSum;
    }

    /**
     * @return string
     */
    public function getNettoSumFormatted()
    {
        $sum = $this->getNettoSum();

        if ($sum == 0) {
            return '';
        }

        switch ($this->calculation) {
            default:
            case Calc::CALCULATION_COMPLEMENT:
                if ($sum > 0) {
                    return '+' . Currencies::getDefaultCurrency()->format($sum);
                }

                return Currencies::getDefaultCurrency()->format($sum);

            case Calc::CALCULATION_PERCENTAGE:
                if ($this->getNettoSum()) {
                    $sum = Currencies::getDefaultCurrency()->format($sum);
                    return '(' . $this->value . '%) ' . $sum;
                }

                return $this->value . '%';
        }
    }

    /**
     * @return string
     */
    public function getBruttoSumFormatted()
    {
        if (!$this->bruttoSum) {
            return $this->getNettoSumFormatted();
        }

        $sum = $this->getBruttoSum();

        if ($sum == 0) {
            return '';
        }

        switch ($this->calculation) {
            default:
            case Calc::CALCULATION_COMPLEMENT:
                if ($sum > 0) {
                    return '+' . Currencies::getDefaultCurrency()->format($sum);
                }

                return Currencies::getDefaultCurrency()->format($sum);

            case Calc::CALCULATION_PERCENTAGE:
                if ($this->getNettoSum()) {
                    $sum = Currencies::getDefaultCurrency()->format($sum);
                    return '(' . $this->value . '%) ' . $sum;
                }

                return $this->value . '%';
        }
    }

    /**
     * Set the calculated sum (brutto)
     *
     * @param Calc $Calc - calculation object
     * @param int|double|float $sum - sum
     */
    public function setBruttoSum(Calc $Calc, $sum)
    {
        if (is_numeric($sum)) {
            $this->bruttoSum = $sum;
        }
    }

    /**
     * @return bool|int|float|double
     */
    public function getBruttoSum()
    {
        return $this->bruttoSum;
    }

    /**
     * Returns the price factor as an array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'title'             => $this->getTitle(),
            'description'       => $this->getDescription(),
            'calculation'       => $this->getCalculation(),
            'calculation_basis' => $this->getCalculationBasis(),
            'value'             => $this->getValue(),
            'valueText'         => $this->getValueText(),
            'priority'          => $this->getPriority(),
            'visible'           => $this->isVisible()
        );
    }
}
