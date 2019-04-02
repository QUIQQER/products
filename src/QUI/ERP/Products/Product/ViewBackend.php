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
class ViewBackend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
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
        $attributes = [
            'id'          => $this->getId(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'image'       => false
        ];

        try {
            $attributes['image'] = $this->getImage()->getUrl(true);
        } catch (QUI\Exception $Exception) {
        }


        /* @var $Price QUI\ERP\Money\Price */
        $Price = $this->getPrice();

        $attributes['price_netto']    = $Price->value();
        $attributes['price_currency'] = $Price->getCurrency()->getCode();

        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        }

        // fields
        $fields    = [];
        $fieldList = $this->getFields();

        /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
        foreach ($fieldList as $Field) {
            $fields[] = \array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );
        }

        if (!empty($fields)) {
            $attributes['fields'] = $fields;
        }

        // categories
        $categories = [];
        $catList    = $this->getCategories();

        /* @var $Category QUI\ERP\Products\Category\Category */
        foreach ($catList as $Category) {
            $categories[] = $Category->getId();
        }

        if (!empty($categories)) {
            $attributes['categories'] = \implode(',', $categories);
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
     * @return QUI\ERP\Money\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Money\Price(
            $this->getAttribute('price'),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * @return QUI\ERP\Money\Price
     * @throws QUI\Exception
     */
    public function getMinimumPrice()
    {
        return $this->Product->getMinimumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * @return QUI\ERP\Money\Price
     * @throws QUI\Exception
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
     *
     * @throws QUI\ERP\Products\Product\Exception
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
     *
     * @throws QUI\ERP\Products\Product\Exception
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
     * @throws QUI\Exception
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

    /**
     * @return bool
     */
    public function hasOfferPrice()
    {
        return $this->getProduct()->hasOfferPrice();
    }

    /**
     * @return false|QUI\ERP\Products\Interfaces\UniqueFieldInterface
     */
    public function getOriginalPrice()
    {
        return $this->getProduct()->getOriginalPrice();
    }

    //region calculation

    /**
     * @param null $Calc
     * @return mixed
     */
    public function calc($Calc = null)
    {
        return $this->getProduct()->calc($Calc);
    }

    /**
     * @param null $Calc
     * @return mixed
     */
    public function resetCalculation()
    {
        return $this->getProduct()->resetCalculation();
    }

    //endregion
}
