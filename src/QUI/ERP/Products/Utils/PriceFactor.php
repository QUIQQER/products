<?php

/**
 * This file contains QUI\ERP\Products\Utils
 */

namespace QUI\ERP\Products\Utils;

use QUI;

use function get_class;
use function is_numeric;
use function is_string;

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
    protected string $identifier = '';

    /**
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var int
     */
    protected int $priority = 0;

    /**
     * Value for the calculation
     *
     * @var integer|float
     */
    protected $value = 0;

    /**
     * @var int|float
     */
    protected $sum = 0;

    /**
     * @var integer|float
     */
    protected $nettoSum = 0;

    /**
     * @var integer|float
     */
    protected $bruttoSum = 0;

    /**
     * @var bool|float|int
     */
    protected $vat = false;

    /**
     * @var integer|float
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
    protected bool $visible = true;

    /**
     * @var string
     */
    protected string $type = PriceFactors::DEFAULT_TYPE;

    /**
     * Basis calculation
     * of which calculation basis should be calculated
     *
     * @var int
     */
    protected int $basis = QUI\ERP\Accounting\Calc::CALCULATION_BASIS_NETTO;

    /**
     * Percent or complement?
     * @var int
     */
    protected int $calculation = QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT;

    /**
     * @var QUI\ERP\Currency\Currency|null
     */
    protected ?QUI\ERP\Currency\Currency $Currency = null;

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
    public function __construct(array $params = [])
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
            $this->setCalculation((int)$params['calculation']);
        }

        if (isset($params['basis'])) {
            $this->setCalculationBasis((int)$params['basis']);
        }

        if (isset($params['calculation_basis'])) {
            $this->setCalculationBasis((int)$params['calculation_basis']);
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
            $this->visible = (bool)$params['visible'];
        }

        if (isset($params['sum'])) {
            $this->setSum($params['sum']);
        }

        if (isset($params['nettoSum'])) {
            $this->setNettoSum($params['nettoSum']);
        }

        if (isset($params['identifier']) && is_string($params['identifier'])) {
            $this->identifier = $params['identifier'];
        }

        if (isset($params['currency'])) {
            $this->setCurrency($params['currency']);
        } else {
            $this->Currency = QUI\ERP\Defaults::getCurrency();
        }
    }

    //region getter

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return int
     */
    public function getCalculation(): int
    {
        return $this->calculation;
    }

    /**
     * @return int
     */
    public function getCalculationBasis(): int
    {
        return $this->basis;
    }

    /**
     * @return QUI\ERP\Currency\Currency
     */
    public function getCurrency(): QUI\ERP\Currency\Currency
    {
        return $this->Currency;
    }

    /**
     * Return the value type
     * it can be 10% => 10
     * it can be 10€ => 10
     *
     * @return integer|float
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
    public function getValueText(): string
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
                    return '+' . $this->getSumFormatted();
                }

                return $this->Currency->format($this->value);

            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                return $this->value . '%';
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
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * @return int|float
     */
    public function getNettoSum()
    {
        return $this->nettoSum;
    }

    /**
     * @return string
     */
    public function getNettoSumFormatted(): string
    {
        $sum = $this->getNettoSum();

        if ($sum == 0) {
            return '';
        }

        switch ($this->calculation) {
            default:
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT:
                if ($sum > 0) {
                    return '+' . $this->Currency->format($sum);
                }

                return $this->Currency->format($sum);

            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                if ($this->getNettoSum()) {
                    return $this->Currency->format($sum);
                }

                return $this->value . '%';
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
     * @return string
     */
    public function getSumFormatted(): string
    {
        $sum = $this->getSum();

        if ($sum == 0) {
            return '';
        }

        switch ($this->calculation) {
            default:
            case QUI\ERP\Accounting\Calc::CALCULATION_COMPLEMENT:
                if ($sum > 0) {
                    return $this->Currency->format($sum);
                }

                return $this->Currency->format($sum);

            case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                if ($this->getSum()) {
                    return $this->Currency->format($sum);
                }

                return $this->value . '%';
        }
    }

    /**
     * Return the specific vat  (eq: 19%)
     *
     * @return int
     */
    public function getVat(): int
    {
        return $this->vat;
    }

    //endregion


    /**
     * Set the title
     *
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Set the title
     *
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * The value for the calculation
     * 10 -> 10%, 10€, 10$
     *
     * @param integer|float $value - value to calc
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
    public function setValueText(string $value)
    {
        $this->valueText = $value;
    }

    /**
     * Set the priority of the calculation
     *
     * @param int $priority
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * Set the calculation type / method
     *
     * @param int $calculation - Calc::CALCULATION_COMPLEMENT, Calc::CALCULATION_PERCENTAGE
     */
    public function setCalculation(int $calculation)
    {
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
    public function setCalculationBasis(int $basis)
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
     * @param integer|float $vat - 7 = 7%, 19 = 19%
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    }

    /**
     * Set the currency for the price factor
     *
     * @param string $currencyCode
     * @return void
     */
    public function setCurrency(string $currencyCode)
    {
        $OldCurrency = $this->Currency;

        try {
            $this->Currency = QUI\ERP\Currency\Handler::getCurrency($currencyCode);
        } catch (QUI\Exception $Exception) {
            $this->Currency = QUI\ERP\Defaults::getCurrency();
        }

        // convert to the other currency
        if (!$OldCurrency) {
            return;
        }

        try {
            $this->bruttoSum = $OldCurrency->convert($this->bruttoSum, $this->Currency);
            $this->nettoSum = $OldCurrency->convert($this->nettoSum, $this->Currency);
            $this->sum = $OldCurrency->convert($this->sum, $this->Currency);
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Sum method
     */

    /**
     * Set the sum for the display
     *
     * @param int|float $sum - sum
     */
    public function setSum($sum)
    {
        if (is_numeric($sum)) {
            $this->sum = $sum;
        }
    }

    /**
     * Set the netto sum
     *
     * @param int|float $sum - sum
     */
    public function setNettoSum($sum)
    {
        if (is_numeric($sum)) {
            $this->nettoSum = $sum;
        }
    }

    /**
     * Returns the price factor as an array
     *
     * @return array
     */
    public function toArray(): array
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
            'class'             => get_class($this),
            'vat'               => $this->getVat(),
            'currency'          => $this->getCurrency()->getCode()
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
    public function toErpPriceFactor(): QUI\ERP\Accounting\PriceFactors\Factor
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
