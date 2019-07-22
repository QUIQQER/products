<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

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
    protected $searchable = false;

    /**
     * @var null
     */
    protected $defaultValue = null;

    /**
     * ProductAttributeList constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setOptions([
            'entries' => []
        ]);

        parent::__construct($fieldId, $params);

        // set default, if one are set
        $options = $this->getOptions();

        foreach ($options['entries'] as $key => $option) {
            if (isset($option['selected']) && $option['selected']) {
                $this->value        = $key;
                $this->defaultValue = $key;
            }
        }
    }

    /**
     * Set a field option
     *
     * @param string $option - option name
     * @param mixed $value - option value
     */
    public function setOption($option, $value)
    {
        parent::setOption($option, $value);

        if ($option == 'entries') {
            if (\is_array($value)) {
                foreach ($value as $key => $val) {
                    if (isset($val['selected']) && $val['selected']) {
                        $this->value        = $key;
                        $this->defaultValue = $key;
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
     * ));
     */
    public function addEntry($entry = [])
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
            'default'       => false,
            'quantityInput' => true
        ];

        foreach ($options as $k => $v) {
            if (isset($entry[$k])) {
                $value[$k] = $entry[$k];
            }
        }

        $entries   = $this->options['entries'];
        $entries[] = $value;

        $this->options['entries'] = $entries;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if (!\is_null($this->value)) {
            return $this->value;
        }

        return $this->defaultValue;
    }

    /**
     * Return the custom value entry from the user
     *
     * @return string|false
     */
    public function getUserInput()
    {
        if (!\is_null($this->value)) {
            $value = \json_decode($this->value, true);

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
    public function getFrontendView()
    {
        return new UnitSelectFrontendView(
            $this->getFieldDataForView()
        );
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UnitSelect';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param array
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        $invalidException = [
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId'    => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType'  => $this->getType()
            ]
        ];

        if (!\is_string($value) && !\is_array($value)) {
            throw new QUI\ERP\Products\Field\Exception($invalidException);
        }

        if (\is_string($value)) {
            $value = \json_decode($value, true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception($invalidException);
            }
        }

        if (!\array_key_exists('id', $value) || !\array_key_exists('quantity', $value)) {
            throw new QUI\ERP\Products\Field\Exception($invalidException);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param string|array $value
     * @return array|null
     */
    public function cleanup($value)
    {
        $defaultValue = null;
        $options      = $this->getOptions();
        $entries      = $options['entries'];

        foreach ($entries as $id => $entry) {
            if ($entry['default']) {
                $defaultValue = [
                    'id'       => $id,
                    'quantity' => false
                ];

                break;
            }
        }

        if (empty($value)) {
            return $defaultValue;
        }

        if (!\is_string($value) && !\is_array($value)) {
            return $defaultValue;
        }

        if (\is_string($value)) {
            $value = \json_decode($value, true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                return $defaultValue;
            }
        }

        if (!\array_key_exists('id', $value) || !\array_key_exists('quantity', $value)) {
            return $defaultValue;
        }

        if (!isset($entries[$value['id']])) {
            return $defaultValue;
        }

        if ($entries[$value['id']]['quantityInput']) {
            $value['quantity'] = (int)$value['quantity'];
        } else {
            $value['quantity'] = false;
        }

        return $value;
    }

    /**
     * Get field value title by value
     *
     * @param int $value
     * @param QUI\Locale|null $Locale (optional) - default: QUI::getLocale()
     * @return string
     */
    public function getTitleByValue($value, QUI\Locale $Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $options = $this->getOptions();
        $entries = $options['entries'];
        $lang    = $Locale->getCurrent();

        if (empty($value['id']) || empty($entries[$value['id']])) {
            return '-';
        }

        if (empty($entries[$value['id']]['title'][$lang])) {
            return '-';
        }

        return $entries[$value['id']]['title'][$lang];
    }
}
