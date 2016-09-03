<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Product backend view
 *
 * @package QUI\ERP\Products\Product
 */
class ViewBackend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @var Product
     */
    protected $Product;

    /**
     * View constructor.
     * @param Model $Product
     */
    public function __construct(Model $Product)
    {
        $this->Product = $Product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->Product->getId();
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $attributes = array(
            'id'          => $this->getId(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'image'       => false
        );

        try {
            $attributes['image'] = $this->getImage()->getUrl(true);
        } catch (QUI\Exception $Exception) {
        }


        /* @var $Price QUI\ERP\Products\Utils\Price */
        $Price = $this->getPrice();

        $attributes['price_netto']    = $Price->getNetto();
        $attributes['price_currency'] = $Price->getCurrency()->getCode();

        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        }

        // fields
        $fields    = array();
        $fieldList = $this->getFields();

        /* @var $Field QUI\ERP\Products\Interfaces\Field */
        foreach ($fieldList as $Field) {
            $fields[] = array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );
        }

        if (!empty($fields)) {
            $attributes['fields'] = $fields;
        }

        // categories
        $categories = array();
        $catList    = $this->getCategories();

        /* @var $Category QUI\ERP\Products\Category\Category */
        foreach ($catList as $Category) {
            $categories[] = $Category->getId();
        }

        if (!empty($categories)) {
            $attributes['categories'] = implode(',', $categories);
        }

        return $attributes;
    }

    /**
     * @return Model|Product
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getTitle($Locale = false)
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false)
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false)
    {
        return $this->Product->getContent($Locale);
    }

    /**
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Products\Utils\Price(
            $this->getAttribute('price'),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getMinimumPrice()
    {
        return $this->Product->getMinimumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getMaximumPrice()
    {
        return $this->Product->getMaximumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * Get a FieldView
     *
     * @param integer $fieldId
     * @return QUI\ERP\Products\Field\View
     */
    public function getFieldView($fieldId)
    {
        return $this->getProduct()->getField($fieldId)->getBackendView();
    }

    /**
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        return $this->getProduct()->getFieldsByType($type);
    }

    /**
     * @param int $fieldId
     * @return QUI\ERP\Products\Field\Field
     * @throws Exception
     */
    public function getField($fieldId)
    {
        return $this->getProduct()->getField($fieldId);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->getProduct()->getFields();
    }

    /**
     * @param int $fieldId
     * @return mixed
     */
    public function getFieldValue($fieldId)
    {
        return $this->getProduct()->getFieldValue($fieldId);
    }

    /**
     * @return null|QUI\ERP\Products\Category\Category
     */
    public function getCategory()
    {
        return $this->getProduct()->getCategory();
    }

    /**
     * @return QUI\Projects\Media\Image
     * @throws Exception
     */
    public function getImage()
    {
        return $this->getProduct()->getImage();
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        return $this->getProduct()->getCategories();
    }
}
