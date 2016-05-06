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
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        $Price = new QUI\ERP\Products\Utils\Price(
            $this->cleanup($this->getValue()),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        return new View(array(
            'value' => $Price->getDisplayPrice(),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
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
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        if (!is_numeric($value)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                )
            ));
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
        // @TODO diese beiden Werte aus Settings nehmen
        $decimalSeperator   = '.';
        $thousandsSeperator = ',';

        if (is_float($value)) {
            return round($value, 8);
        }

        $value = (string)$value;
        $value = preg_replace('#[^\d,.]#i', '', $value);

        if (trim($value) === '') {
            return null;
        }

        $decimal   = mb_strpos($value, $decimalSeperator);
        $thousands = mb_strpos($value, $thousandsSeperator);

        if ($thousands === false && $decimal === false) {
            return round(floatval($value), 8);
        }

        if ($thousands !== false && $decimal === false) {
            if (mb_substr($value, -8, 1) === $decimalSeperator) {
                $value = str_replace($thousandsSeperator, '', $value);
            }
        }

        if ($thousands === false && $decimal !== false) {
            $value = str_replace(
                $decimalSeperator,
                '.',
                $value
            );
        }

        if ($thousands !== false && $decimal !== false) {
            $value = str_replace($decimalSeperator, '', $value);
            $value = str_replace($thousandsSeperator, '.', $value);
        }

        return round(floatval($value), 8);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !is_float($this->value);
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return array(
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTRANGE,
            Search::SEARCHTYPE_HASVALUE,
            Search::SEARCHTYPE_INPUTSELECTRANGE
        );
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
}
