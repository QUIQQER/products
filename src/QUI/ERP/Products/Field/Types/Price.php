<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Price
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class Price
 * @package QUI\ERP\Products\Field
 */
class Price extends QUI\ERP\Products\Field\Field
{
    /**
     * @var string
     */
    protected $columnType = 'DOUBLE';

    /**
     * @var int
     */
    protected $searchDataType = Search::SEARCHDATATYPE_NUMERIC;

    /**
     * Official currency code (i.e. EUR)
     *
     * @var string
     */
    protected $currencyCode = null;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getAttributes());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        $Price = new QUI\ERP\Money\Price(
            $this->cleanup($this->getValue()),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        return new View([
            'id'       => $this->getId(),
            'value'    => $Price->getDisplayPrice(),
            'title'    => $this->getTitle(),
            'prefix'   => $this->getAttribute('prefix'),
            'suffix'   => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ]);
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Price';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        if ($this->getId() == QUI\ERP\Products\Handler\Fields::FIELD_PRICE) {
            return '';
        }

        return 'package/quiqqer/products/bin/controls/fields/types/PriceSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        $value = $this->cleanup($value);

        if (!\is_numeric($value)) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType'  => $this->getType()
                ]
            ]);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * Precision: 8 (important for currencies like BitCoin)
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup($value)
    {
        if (\is_array($value)) {
            return null;
        }

        if (\is_string($value) && \trim($value) === '') {
            return null;
        }

        if (\is_float($value)) {
            return \round($value, QUI\ERP\Defaults::getPrecision());
        }

        return QUI\ERP\Money\Price::validatePrice($value);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !\is_float($this->value);
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return [
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTRANGE,
            Search::SEARCHTYPE_INPUTSELECTRANGE,
            Search::SEARCHTYPE_HASVALUE
        ];
    }

    /**
     * Get default search type
     *
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_SELECTRANGE;
    }

    /**
     * Calculates a range with individual steps between a min and a max number
     *
     * @param integer|float $min
     * @param integer|float $max
     * @return array - contains values from min to max with calculated steps inbetween
     */
    public function calculateValueRange($min, $max)
    {
        // add tax to max value
        $maxTaxValue = (100 + QUI\ERP\Tax\Utils::getMaxTax()) / 100;
        $max         *= $maxTaxValue;

        if ($min < 10) {
            $start = 0;
        } else {
            // round down to lowest 10 (e.g.: 144 = 140; 2554 = 2550)
            $floorPrecision = 1;

            if ((string)\mb_strlen((int)$min) > 1) {
                $floorPrecision = 10;
            }

            $start = \floor($min / $floorPrecision) * $floorPrecision;
            $start = (int)$start;
        }

        $value   = $start;
        $range[] = $value;

        while ($value < $max) {
            if (\round($value, 1) < 1) {
                $add = 0.1;
            } else {
                $add = 1;
                $i   = 10;

                while ($value >= $i) {
                    $i   *= 10;
                    $add *= 10;
                }

                $value = \floor($value / $add) * $add;
            }

            $value   += $add;
            $range[] = $value;
        }

        return $range;
    }
}
