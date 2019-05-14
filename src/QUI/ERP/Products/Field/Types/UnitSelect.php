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
     * @return ProductAttributeListFrontendView
     */
    public function getFrontendView()
    {
        return new ProductAttributeListFrontendView(
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
     * @param integer|string $value - User value = "[key, user value]"
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

        if (!\is_numeric($value)) {
            if (\is_array($value)) {
                $value = \json_encode($value);
            }

            $value = \json_decode($value, true);

            if (!isset($value[0]) || !isset($value[1])) {
                throw new QUI\ERP\Products\Field\Exception($invalidException);
            }

            //$customValue = $value[1];
            $value = $value[0];
        }

        if (!\is_numeric($value)) {
            throw new QUI\ERP\Products\Field\Exception($invalidException);
        }

        $value   = (int)$value;
        $options = $this->getOptions();

        if (!isset($options['entries'])) {
            throw new QUI\ERP\Products\Field\Exception($invalidException);
        }

        $entries = $options['entries'];

        if (!isset($entries[$value])) {
            throw new QUI\ERP\Products\Field\Exception($invalidException);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param integer $value
     * @return int|null
     */
    public function cleanup($value)
    {
        $check = [];

        if (\is_string($value)) {
            $check = \json_decode($value, true);

            if (!isset($check[0]) || !isset($check[1])) {
                return null;
            }

            if (!\is_numeric($check[0])) {
                return null;
            }

            return $value;
        }

        if (\is_array($value)) {
            if (!isset($check[0]) || !isset($check[1])) {
                return null;
            }

            if (!\is_numeric($check[0])) {
                return null;
            }

            return $value;
        }


        if (empty($value) && !\is_int($value) && $value != 0) {
            return null;
        }

        if (!\is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }
}
