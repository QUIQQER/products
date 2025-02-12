<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;

use function array_key_exists;
use function is_array;
use function is_null;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

/**
 * Class UnitSelect
 *
 * Select a unit from a predefined list of values and use an input to define
 * the quantity.
 */
class UnitSelect extends QUI\ERP\Products\Field\Field
{
    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * @var null
     */
    protected mixed $defaultValue = null;

    /**
     * ProductAttributeList constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct(int $fieldId, array $params)
    {
        $this->setOptions([
            'entries' => []
        ]);

        parent::__construct($fieldId, $params);

        // set default, if one are set
        $options = $this->getOptions();

        foreach ($options['entries'] as $key => $option) {
            if (isset($option['selected']) && $option['selected']) {
                $this->value = $key;
            }
        }
    }

    /**
     * Set a field option
     *
     * @param string $option - option name
     * @param mixed $value - option value
     */
    public function setOption(string $option, mixed $value): void
    {
        parent::setOption($option, $value);

        if ($option == 'entries') {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    if (isset($val['selected']) && $val['selected']) {
                        $this->value = $key;
                    }
                }
            }
        }
    }

    /**
     * Add unit value entry
     *
     * @param array $entry - data entry
     *
     * @example $this->addEntry(array(
     *       'title'         => '',      // translation json string {de: "", en: ""}
     *       'default'       => true,    // true/false - is selected by default
     *       'quantityInput' => true,    // true/false - allow user input to define quantity
     *       'defaultQuantity' => false / int,    // false or integer - default quantity value
     * ));
     */
    public function addEntry(array $entry = []): void
    {
        if (empty($entry)) {
            return;
        }

        if (!isset($entry['title'])) {
            return;
        }

        $value = [
            'title' => $entry['title']
        ];

        // Default options
        $options = [
            'default' => false,
            'quantityInput' => true,
            'defaultQuantity' => false
        ];

        foreach ($options as $k => $v) {
            if (isset($entry[$k])) {
                $value[$k] = $entry[$k];
            }
        }

        $entries = $this->options['entries'];
        $entries[] = $value;

        $this->options['entries'] = $entries;
    }

    /**
     * @return array|string|null
     */
    public function getValue(): array|string|null
    {
        if (!is_null($this->value)) {
            return $this->value;
        }

        return $this->getDefaultValue();
    }

    /**
     * Return the default value
     *
     * @return array|null
     */
    public function getDefaultValue(): ?array
    {
        $options = $this->getOptions();
        $entries = $options['entries'];

        foreach ($entries as $id => $entry) {
            if ($entry['default']) {
                if (!empty($entry['quantityInput'])) {
                    if (!empty($entry['defaultQuantity'])) {
                        return [
                            'id' => $id,
                            'quantity' => $entry['defaultQuantity']
                        ];
                    }
                } else {
                    return [
                        'id' => $id,
                        'quantity' => null
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Return the custom value entry from the user
     *
     * @return string|false
     */
    public function getUserInput(): bool|string
    {
        if (!is_null($this->value)) {
            $value = json_decode($this->value, true);

            if (isset($value[1])) {
                return $value[1];
            }
        }

        return false;
    }

    /**
     * Return the FrontendView
     *
     * @return UnitSelectFrontendView
     */
    public function getFrontendView(): UnitSelectFrontendView
    {
        return new UnitSelectFrontendView(
            $this->getFieldDataForView()
        );
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UnitSelect';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings';
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

        $invalidException = [
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId' => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType' => $this->getType()
            ]
        ];

        if (!is_string($value) && !is_array($value)) {
            throw new Exception($invalidException);
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception($invalidException);
            }
        }

        if (!array_key_exists('id', $value) || !array_key_exists('quantity', $value)) {
            throw new Exception($invalidException);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return array|null
     */
    public function cleanup(mixed $value): mixed
    {
        $defaultValue = $this->getDefaultValue();
        $options = $this->getOptions();
        $entries = $options['entries'];

        if (empty($value)) {
            return $defaultValue;
        }

        if (!is_string($value) && !is_array($value)) {
            return $defaultValue;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $defaultValue;
            }
        }

        if (
            !array_key_exists('id', $value) ||
            !array_key_exists('quantity', $value)
        ) {
            return $defaultValue;
        }

        if (!isset($entries[$value['id']])) {
            return $defaultValue;
        }

        if ($entries[$value['id']]['quantityInput']) {
            if (empty($value['quantity']) && !is_numeric($value['quantity'])) {
                if (!empty($value['defaultQuantity'])) {
                    $value['quantity'] = $value['defaultQuantity'];
                } else {
                    return $defaultValue;
                }
            } else {
                $value['quantity'] = (float)$value['quantity'];
            }
        } else {
            $value['quantity'] = false;
        }

        return $value;
    }

    /**
     * Get field value title by value
     *
     * @param mixed $value
     * @param QUI\Locale|null $Locale (optional) - default: QUI::getLocale()
     * @return string
     */
    public function getTitleByValue(mixed $value, null | QUI\Locale $Locale = null): string
    {
        if (empty($value)) {
            return '-';
        }

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $options = $this->getOptions();
        $entries = $options['entries'];
        $lang = $Locale->getCurrent();

        if (empty($value['id']) || empty($entries[$value['id']])) {
            return '-';
        }

        if (empty($entries[$value['id']]['title'][$lang])) {
            return '-';
        }

        return $entries[$value['id']]['title'][$lang];
    }
}
