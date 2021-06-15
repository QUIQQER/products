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
class PriceFactor implements QUI\ERP\Products\Interfaces\PriceFactorInterface
{
    /**
     * @var string
     */
    protected $identifier = '';

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
     * @var int|float|double
     */
    protected $sum = 0;

    /**
     * @var integer|double|float
     */
    protected $nettoSum = 0;

    /**
     * @var integer|double|float
     */
    protected $bruttoSum = 0;

    /**
     * @var bool
     */
    protected $vat = false;

    /**
     * @var integer|double|float
     */
    protected $calculatedSum = 0;

    /**
     * @var string|false
     */
    protected $valueText = false;

    /**
     * Is the price factor visible
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
    protected $basis = QUI\ERP\Accounting\Calc::CALCULATION_BASIS_NETTO;

    /**
     * Percent or complement?
     * @var int
     */
    protected $calculation = QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT;

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
     *      'visible' => true,
     *      'vat' => 19 // automatic
     * )
     */
    public function __construct($params = [])
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

        if (isset($params['calculation_basis'])) {
            $this->setCalculationBasis($params['calculation_basis']);
        }

        if (isset($params['value'])) {
            $this->setValue($params['value']);
        }

        if (isset($params['valueText'])) {
            $this->setValueText($params['valueText']);
        }

        if (isset($params['vat'])) {
            $this->setVat((int)$params['vat']);
        }

        if (isset($params['visible'])) {
            if (\is_bool($params['visible'])) {
                $this->visible = (bool)$params['visible'];
            } else {
                $this->visible = $params['visible'] ? true : false;
            }
        }

        if (isset($params['sum'])) {
            $this->setSum($params['sum']);
        }

        if (isset($params['nettoSum'])) {
            $this->setNettoSum($params['nettoSum']);
        }

        if (isset($params['identifier']) && \is_string($params['identifier'])) {
            $this->identifier = $params['identifier'];
        }
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
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
     * Return the value type
     * it can be 10% => 10
     * it can be 10€ => 10
     *
     * @return integer|float|double
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the text for the value type
     * (Return the prefix text)
     *
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

        if ($this->value == 0) {
            return '';
        }

        switch ($this->calculation) {
            default:
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT:
                if ($this->value > 0) {
                    return '+'.$this->getSumFormatted();
                }

                return Currencies::getDefaultCurrency()->format($this->value);

            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                return $this->value.'%';
        }
    }

    /**
     * @return bool
     */
    public function hasValueText(): bool
    {
        return !empty($this->valueText);
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
        if (\is_string($title)) {
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
        if (\is_string($description)) {
            $this->description = $description;
        }
    }

    /**
     * The value for the calculation
     * 10 -> 10%, 10€, 10$
     *
     * @param integer|float|double $value - value to calc
     */
    public function setValue($value)
    {
        if (\is_numeric($value)) {
            $this->value = $value;
        }
    }

    /**
     * The text for the value presentation
     * If no value text is set, normal value is used
     *
     * @param string|false $value
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
        if (\is_int($priority)) {
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
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT:
            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLETE:
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
            case QUI\ERP\Accounting\Calc::CALCULATION_BASIS_NETTO:
            case QUI\ERP\Accounting\Calc::CALCULATION_BASIS_CURRENTPRICE:
            case QUI\ERP\Accounting\Calc::CALCULATION_BASIS_VAT_BRUTTO:
            case QUI\ERP\Accounting\Calc::CALCULATION_GRAND_TOTAL:
                $this->basis = $basis;
                break;
        }
    }

    /**
     * Sets the vat % value (eq: 19%)
     *
     * @param integer $vat - 7 = 7%, 19 = 19%
     */
    public function setVat($vat)
    {
        $this->vat = (int)$vat;
    }

    /**
     * Return the specific vat  (eq: 19%)
     *
     * @return bool
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Sum method
     */

    /**
     * Set the sum for the display
     *
     * @param int|double|float $sum - sum
     */
    public function setSum($sum)
    {
        if (\is_numeric($sum)) {
            $this->sum = $sum;
        }
    }

    /**
     * @return float|int
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * @return float|int|string
     */
    public function getSumFormatted()
    {
        $sum = $this->getSum();

        if ($sum == 0) {
            return '';
        }

        switch ($this->calculation) {
            default:
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT:
                if ($sum > 0) {
                    return Currencies::getDefaultCurrency()->format($sum);
                }

                return Currencies::getDefaultCurrency()->format($sum);

            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                if ($this->getSum()) {
                    $sum = Currencies::getDefaultCurrency()->format($sum);

                    return $sum;
                }

                return $this->value.'%';
        }
    }

    /**
     * Set the netto sum
     *
     * @param int|double|float $sum - sum
     */
    public function setNettoSum($sum)
    {
        if (\is_numeric($sum)) {
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
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT:
                if ($sum > 0) {
                    return '+'.Currencies::getDefaultCurrency()->format($sum);
                }

                return Currencies::getDefaultCurrency()->format($sum);

            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                if ($this->getNettoSum()) {
                    $sum = Currencies::getDefaultCurrency()->format($sum);

                    return $sum;
                }

                return $this->value.'%';
        }
    }

    /**
     * Returns the price factor as an array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'identifier'        => $this->identifier,
            'title'             => $this->getTitle(),
            'description'       => $this->getDescription(),
            'calculation'       => $this->getCalculation(),
            'calculation_basis' => $this->getCalculationBasis(),
            'sum'               => $this->getSum(),
            'sumFormatted'      => $this->getSumFormatted(),
            'nettoSum'          => $this->getNettoSum(),
            'nettoSumFormatted' => $this->getNettoSumFormatted(),
            'value'             => $this->getValue(),
            'valueText'         => $this->getValueText(),
            'priority'          => $this->getPriority(),
            'visible'           => $this->isVisible(),
            'class'             => \get_class($this),
            'vat'               => $this->getVat()
        ];
    }

    /**
     * Parse this price factor to erp factor
     * An ERP Factor is not changeable
     *
     * @return QUI\ERP\Accounting\PriceFactors\Factor
     *
     * @throws QUI\ERP\Exception
     */
    public function toErpPriceFactor()
    {
        return new QUI\ERP\Accounting\PriceFactors\Factor([
            'identifier'        => $this->identifier,
            'title'             => $this->getTitle(),
            'description'       => $this->getDescription(),
            'sum'               => $this->getSum(),
            'sumFormatted'      => $this->getSumFormatted(),
            'calculation'       => $this->getCalculation(),
            'calculation_basis' => $this->getCalculationBasis(),
            'nettoSum'          => $this->getNettoSum(),
            'nettoSumFormatted' => $this->getNettoSumFormatted(),
            'visible'           => $this->isVisible(),
            'vat'               => $this->getVat(),
            'valueText'         => $this->getValueText(),
            'value'             => $this->getValue()
        ]);
    }
}
