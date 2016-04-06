<?php

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Field\UniqueField;

/**
 * Class UniqueProduct
 */
class UniqueProduct extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * UniqueProduct constructor.
     *
     * @param integer $pid - Product ID
     * @param $attributes - attributes
     */
    public function __construct($pid, $attributes = array())
    {
        $this->id = $pid;

        // fields
        $this->parseFieldsFromAttributes($attributes);
        return;

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            $this->fields[] = $Field->createUniqueField();

            $params = array(
                'value' => $Field->getValue(),
                'type' => $Field->getType(),
                'customfield' => $Field->isCustomField()
            );

            if ($Field->isCustomField()) {
                $this->setAttribute('field-' . $Field->getId(), $params);
                continue;
            }

            $value   = $Field->getValue();
            $fieldId = $Field->getId();

            if (isset($attributes[$fieldId])) {
                $value = $attributes[$fieldId];
            }

            $Field->validate($value);

            $params['value'] = $value;

            $this->setAttribute('field-' . $Field->getId(), $params);


            // calc eigenschaften


        }
    }

    /**
     * Parse field data
     *
     * @param array $attributes - product attributes
     */
    protected function parseFieldsFromAttributes($attributes = array())
    {
        if (!isset($attributes['fields'])) {
            return;
        }

        $fields = $attributes['fields'];

        foreach ($fields as $field) {
            $this->fields[] = new UniqueField($field['id'], $field);
        }
    }

    /**
     * @return array
     */
    public function getPriceFactors()
    {
        return array();
    }

    /**
     * Unique identifier
     *
     * @return string
     */
    public function getCacheIdentifier()
    {
        return md5(serialize($this->getAttributes()));
    }

    /**
     * Return the Product-ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the translated title
     *
     * @param bool|\QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title   = $this->getField(Fields::FIELD_TITLE);
        $values  = $Title->getValue();

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     * Return the translated description
     *
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title   = $this->getField(Fields::FIELD_SHORT_DESC);
        $values  = $Title->getValue();

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     * Return the the wanted field
     *
     * @param int $fieldId
     * @return QUI\ERP\Products\Field\UniqueField|false
     */
    public function getField($fieldId)
    {
        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($this->fields as $Field) {
            if ($Field->getId() == $fieldId) {
                return $Field;
            }
        }

        return false;
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        $fields = $this->getFields();
        $result = array();

        foreach ($fields as $Field) {
            if ($Field && $Field->getType() == $type) {
                $result[] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return a price object
     *
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        $Currency   = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $PriceField = $this->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRICE);

        return new QUI\ERP\Products\Utils\Price($PriceField->getValue(), $Currency);
    }

    /**
     * Return the value of the wanted field
     *
     * @param int $fieldId
     * @return mixed|false
     */
    public function getFieldValue($fieldId)
    {
        $Field = $this->getField($fieldId);

        if ($Field) {
            return $Field->getValue();
        }

        return false;
    }
}
