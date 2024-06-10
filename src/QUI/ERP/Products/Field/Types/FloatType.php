<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\FloatType
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
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
    protected string $columnType = 'DOUBLE';

    /**
     * @var int|bool
     */
    protected int|bool $searchDataType = Search::SEARCHDATATYPE_NUMERIC;

    /**
     * @return View
     */
    public function getBackendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @TODO value formatierung aus settings (nachkommastellen, separatoren)
     *
     * @return View
     */
    public function getFrontendView(): View
    {
        $attributes = $this->getFieldDataForView();
        $value = $this->getValue();

        if (!$value) {
            $value = 0;
        }

        $attributes['value'] = QUI::getLocale()->formatNumber($value);

        return new View($attributes);
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/FloatType';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws Exception
     */
    public function validate(mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        if (!is_numeric($value)) {
            throw new Exception([
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
     * @return float|null
     */
    public function cleanup(mixed $value): ?float
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
    public function isEmpty(): bool
    {
        return !is_float($this->value);
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes(): array
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
     * @return string|null
     */
    public function getDefaultSearchType(): ?string
    {
        return Search::SEARCHTYPE_SELECTRANGE;
    }
}
