<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\FloatType
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class FloatType extends QUI\ERP\Products\Field\Field
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
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @TODO value formatierung aus settings (nachkommastellen, separatoren)
     *
     * @return View
     */
    public function getFrontendView()
    {
        $attributes = $this->getFieldDataForView();
        $value      = $this->getValue();

        $localeCode = QUI::getLocale()->getLocalesByLang(
            QUI::getLocale()->getCurrent()
        );

        $Formatter = new \NumberFormatter(
            $localeCode[0],
            \NumberFormatter::DECIMAL
        );

        if (is_string($value)) {
            $value = floatval($value);
        }

        $attributes['value'] = $Formatter->format($value);

        return new View($attributes);
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/FloatType';
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

        if (!is_numeric($value)) {
            throw new QUI\ERP\Products\Field\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType'  => $this->getType()
                )
            ));
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup($value)
    {
        // @TODO diese beiden Werte aus Settings nehmen (s. Price)
        $decimalSeperator   = '.';
        $thousandsSeperator = ',';

        if (is_float($value)) {
            return round($value, 4);
        }

        $value = (string)$value;
        $value = preg_replace('#[^\d,.]#i', '', $value);

        if (trim($value) === '') {
            return null;
        }

        $decimal   = mb_strpos($value, $decimalSeperator);
        $thousands = mb_strpos($value, $thousandsSeperator);

        if ($thousands === false && $decimal === false) {
            return round(floatval($value), 4);
        }

        if ($thousands !== false && $decimal === false) {
            if (mb_substr($value, -4, 1) === $decimalSeperator) {
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

        return round(floatval($value), 4);
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
            Search::SEARCHTYPE_INPUTSELECTRANGE,
            Search::SEARCHTYPE_HASVALUE
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
