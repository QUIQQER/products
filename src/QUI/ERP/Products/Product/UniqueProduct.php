<?php

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Utils\PriceFactor;

/**
 * Class UniqueProduct
 *
 * @event onQuiqqerProductsPriceFactorsInit [QUI\ERP\Products\Utils\PriceFactors, QUI\ERP\Products\Interfaces\Product]
 */
class UniqueProduct extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer|float
     */
    protected $quantity = 1;

    /**
     * @var array
     */
    protected $categories = array();

    /**
     * @var null|QUI\ERP\Products\Category\Category
     */
    protected $Category = null;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * Price factors
     *
     * @var QUI\ERP\Products\Utils\PriceFactors
     */
    protected $PriceFactors;

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * UniqueProduct constructor.
     *
     * @param integer $pid - Product ID
     * @param $attributes - attributes
     */
    public function __construct($pid, $attributes = array())
    {
        $this->id         = $pid;
        $this->attributes = $attributes;

        // fields
        $this->parseFieldsFromAttributes($attributes);
        $this->parseCategoriesFromAttributes($attributes);

        // generate the price factors
        $fields = $this->getFields();

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            if (!$Field->isCustomField()) {
                continue;
            }

            $attributes = $Field->getAttributes();

            $Factor = new PriceFactor($attributes['custom_calc']);
            $Factor->setTitle($Field->getTitle());

            $this->PriceFactors->add($Factor);
        }

        QUI::getEvents()->fireEvent(
            'quiqqerProductsPriceFactorsInit',
            array($this->PriceFactors, $this)
        );
    }

    /**
     * Parse the field data
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
     * Parse the category data
     *
     * @param array $attributes
     */
    protected function parseCategoriesFromAttributes($attributes = array())
    {
        if (!isset($attributes['categories'])) {
            return;
        }

        $list       = array();
        $categories = explode(',', $attributes['categories']);

        foreach ($categories as $cid) {
            try {
                $list[] = Categories::getCategory($cid);
            } catch (QUI\Exception $Exception) {
            }
        }

        $this->categories = $list;
    }

    /**
     * @param array $attributes
     */
    protected function parseCategoryFromAttributes($attributes = array())
    {
        if (!isset($attributes['category'])) {
            return;
        }

        try {
            $this->Category = QUI\ERP\Products\Handler\Categories::getCategory(
                $attributes['category']
            );
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Return the price factor list of the product
     *
     * @return QUI\ERP\Products\Utils\PriceFactors
     */
    public function getPriceFactors()
    {
        return $this->PriceFactors;
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

        if (!$Title) {
            return '';
        }

        $values = $Title->getValue();

        if (is_string($values)) {
            return $values;
        }

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

        if (is_string($values)) {
            return $values;
        }

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     * Return the translated content
     *
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title   = $this->getField(Fields::FIELD_CONTENT);

        if (!$Title) {
            return '';
        }

        $values = $Title->getValue();

        if (is_string($values)) {
            return $values;
        }

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     *
     */
    public function getImage()
    {
        $image = $this->getFieldValue(Fields::FIELD_IMAGE);

        var_dump($image);
    }

    /**
     * Return the the wanted field
     *
     * @param int $fieldId
     * @return QUI\ERP\Products\Field\UniqueField|false
     */
    public function getField($fieldId)
    {
        $fields = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
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

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
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
        return QUI\ERP\Products\Utils\Calc::getProductPrice($this);
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

    /**
     * Return the main catgory
     *
     * @return QUI\ERP\Products\Handler\Categories|null
     */
    public function getCategory()
    {
        if ($this->Category) {
            return $this->Category;
        }

        if (!isset($this->attributes['category'])) {
            return $this->Category;
        }

        try {
            $this->Category = Categories::getCategory($this->attributes['category']);
        } catch (QUI\Exception $Exception) {
        }

        return $this->Category;
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }


    /**
     * Set the quantity of the product
     *
     * @param integer|float $quantity
     */
    public function setQuantity($quantity)
    {
        if (!is_numeric($quantity)) {
            return;
        }

        $this->quantity = $quantity;
    }

    /**
     * Return the quantity
     *
     * @reutrn integer|float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Return the product attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes             = parent::getAttributes();
        $attributes['quantity'] = $this->getQuantity();
        $attributes['id']       = $this->getId();
        $attributes['fields']   = $this->getFields();

        return $attributes;
    }
}
