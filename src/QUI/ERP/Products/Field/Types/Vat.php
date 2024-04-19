<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Vat
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;

use function explode;
use function is_numeric;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class Vat extends QUI\ERP\Products\Field\Field
{
    /**
     * @var string
     */
    protected string $columnType = 'SMALLINT';

    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Vat';
    }

    /**
     * Return the frontend view
     */
    public function getFrontendView(): VatFrontendView
    {
        return new VatFrontendView($this->getFieldDataForView());
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

        if (str_contains($value, ':')) {
            $value = explode(':', $value);

            if (isset($value[1])) {
                $value = (int)$value[1];
            } else {
                $value = false;
            }
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

        $value = (int)$value;

        if ($value === -1) {
            return;
        }

        // exists tax?
        $value = self::cleanup($value);
        $Taxes = new QUI\ERP\Tax\Handler();

        try {
            $Taxes->getTaxType($value);
        } catch (QUI\Exception) {
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
     * @return integer
     */
    public function cleanup(mixed $value): int
    {
        if ($value === '') {
            return -1;
        }

        if (str_contains($value, ':')) {
            $value = explode(':', $value);
            $value = $value[1] ?? -1;
        }

        return (int)$value;
    }
}
