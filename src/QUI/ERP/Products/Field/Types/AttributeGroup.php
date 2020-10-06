<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Attributes
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Handler\Search;

/**
 * Class Attributes
 * - Attribute Liste
 *
 * @package QUI\ERP\Products\Field
 *
 * @todo eindeutige ID für option
 */
class AttributeGroup extends QUI\ERP\Products\Field\Field
{
    /**
     * @var int
     */
    protected $searchDataType = Search::SEARCHDATATYPE_TEXT;

    /**
     * @var null
     */
    protected $defaultValue = null;

    /**
     * @var array
     */
    protected $disabled = [];

    /**
     * Attribute group constructor.
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
                $this->value        = $option['valueId'];
                $this->defaultValue = $option['valueId'];
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
                        $this->value        = $val['valueId'];
                        $this->defaultValue = $val['valueId'];
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
            'valueId',
            'selected', // optional
            'disabled', // optional
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
     * disable all entries
     */
    public function disableEntries()
    {
        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['disabled'] = true;
        }
    }

    /**
     * hide all entries
     */
    public function hideEntries()
    {
        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['hide'] = true;
        }
    }

    /**
     * Disable an option
     *
     * @param string|integer $entry
     */
    public function disableEntry($entry)
    {
        $this->options['entries'][$entry]['disabled'] = true;
    }

    /**
     * Enable an option
     *
     * @param string|integer $entry
     */
    public function enableEntry($entry)
    {
        $this->options['entries'][$entry]['disabled'] = false;
    }

    /**
     * Enable an option
     *
     * @param string|integer $entry
     */
    public function showEntry($entry)
    {
        $this->options['entries'][$entry]['hide'] = false;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if ($this->value !== null) {
            return $this->value;
        }

        return $this->defaultValue;
    }

    /**
     * clears the current value of the field
     */
    public function clearValue()
    {
        parent::clearValue();

        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['selected'] = false;
        }
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getValue() === null;
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
        return 'package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings';
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

        $options = $this->getOptions();
        $entries = $options['entries'];

        foreach ($entries as $entry) {
            if ($entry['valueId'] == $value
                || is_numeric($value) && $entry['valueId'] == (int)$value) {
                return;
            }
        }

        throw new QUI\ERP\Products\Field\Exception([
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId'    => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType'  => $this->getType()
            ]
        ]);
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param integer|string $value
     * @return int|null
     */
    public function cleanup($value)
    {
        $check = [];

        if (\is_string($value)) {
            $check = \json_decode($value, true);

            // if no json, check if value exist
            if ($check === null && !\is_numeric($value)) {
                $options = $this->getOptions();
                $entries = $options['entries'];

                foreach ($entries as $key => $entry) {
                    if ($entry['valueId'] == $value) {
                        return $value;
                    }
                }
            }

            if (\is_numeric($value)) {
//                $value   = (int)$value;
                $options = $this->getOptions();
                $entries = $options['entries'];

                // first check if a value id exists with this value
                foreach ($entries as $key => $entry) {
                    if ($entry['valueId'] == $value) {
                        return $value;
                    }
                }

                // use the key
                if (isset($entries[$value])) {
                    return $entries[$value]['valueId'];
                }
            }

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

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return [
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
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
        return Search::SEARCHTYPE_TEXT;
    }
}
