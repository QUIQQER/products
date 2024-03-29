<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\FloatType
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

use function floatval;
use function is_float;
use function is_numeric;
use function mb_strpos;
use function mb_substr;
use function preg_replace;
use function round;
use function str_replace;
use function trim;

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
        $value = $this->getValue();

        $attributes['value'] = QUI::getLocale()->formatNumber($value);

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
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                ]
            ]);
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
        $decimalSeparator = '.';
        $thousandsSeparator = ',';

        if (is_float($value)) {
            return round($value, 4);
        }

        $value = (string)$value;
        $value = preg_replace('#[^\d,.]#i', '', $value);

        if (trim($value) === '') {
            return null;
        }

        $decimal = mb_strpos($value, $decimalSeparator);
        $thousands = mb_strpos($value, $thousandsSeparator);

        if ($thousands === false && $decimal === false) {
            return round(floatval($value), 4);
        }

        if ($thousands !== false && $decimal === false) {
            if (mb_substr($value, -4, 1) === $decimalSeparator) {
                $value = str_replace($thousandsSeparator, '', $value);
            }
        }

        if ($thousands === false && $decimal !== false) {
            $value = str_replace(
                $decimalSeparator,
                '.',
                $value
            );
        }

        if ($thousands !== false && $decimal !== false) {
            $value = str_replace($decimalSeparator, '', $value);
            $value = str_replace($thousandsSeparator, '.', $value);
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
}
