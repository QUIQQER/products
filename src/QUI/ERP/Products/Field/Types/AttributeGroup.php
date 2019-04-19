<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Attributes
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class Attributes
 * - Attribute Liste
 *
 * @package QUI\ERP\Products\Field
 *
 *
 * @todo eindeutige ID für option
 */
class AttributeGroup extends QUI\ERP\Products\Field\Field
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
            'entries'       => [],
            'priority'      => 0,
            'generate_tags' => false
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
     * Add an product attribute entry
     *
     * @param array $entry - data entry
     *
     * @example $this->addEntry(array(
     *       'title' => '',    // translation json string {de: "", en: ""}
     *       'sum'   => '',      // -> 10, 100 -> numbers
     *       'type'  => '',     // optional -> QUI\ERP\Products\Utils\Calc::CALCULATION_PERCENTAGE |
     *                                        QUI\ERP\Products\Utils\Calc::CALCULATION_COMPLEMENT
     *       'selected' => '', // optional
     *       'userinput => ''' // optional
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

        $data = [];

        $available = [
            'title',
            'type',     // optional
            'selected', // optional
        ];

        foreach ($available as $k) {
            if (isset($entry[$k])) {
                $data[$k] = $entry[$k];
            }
        }

        $entries   = $this->options['entries'];
        $entries[] = $data;

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
     * Return the FrontendView
     *
     * @return AttributeGroupFrontendView
     */
    public function getFrontendView()
    {
        return new AttributeGroupFrontendView(
            $this->getFieldDataForView()
        );
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/AttributeGroup';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/AttributeGroup';
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
