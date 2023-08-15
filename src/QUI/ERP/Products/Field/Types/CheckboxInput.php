<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

use function is_string;
use function json_last_error;

/**
 * Class CheckboxInput
 *
 * Represents a checkbox with an input
 */
class CheckboxInput extends QUI\ERP\Products\Field\Field
{
    protected $searchable = false;

    protected $defaultValue = [
        'checked' => false,
        'value' => ''
    ];

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/CheckboxInput';
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

        if (is_string($value)) {
            $value = \json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
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

        if (empty($value)) {
            return;
        }

        if (!isset($value['checked']) || !isset($value['value'])) {
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
     * @return array
     */
    public function cleanup($value)
    {
        if (is_string($value)) {
            $value = \json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->getDefaultValue();
            }
        }

        if (empty($value)) {
            return $this->getDefaultValue();
        }

        if (!isset($value['checked']) || !isset($value['value'])) {
            return $this->getDefaultValue();
        }

        return $value;
    }
}
