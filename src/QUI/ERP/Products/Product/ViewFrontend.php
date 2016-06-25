<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @package QUI\ERP\Products\Product
 */
class ViewFrontend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @var UniqueProduct
     */
    protected $Product;

    /**
     * View constructor.
     *
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
        return QUI\ERP\Products\Utils\Calc::getProductPrice(
            $this->Product->createUniqueProduct()
        );
    }

    /**
     * Get value of field
     *
     * @param integer $fieldId
     * @param bool $affixes (optional) - append suffix and prefix if defined [default: false]
     * @return mixed - formatted field value
     */
    public function getFieldValue($fieldId, $affixes = false)
    {
        return $this->Product->getFieldValue($fieldId);
    }

    /**
     * Return all fields from the wanted type
     *
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        return $this->Product->getFieldsByType($type);
    }

    /**
     * Return the the wanted field
     *
     * @param int $fieldId
     * @return false|QUI\ERP\Products\Field\UniqueField|QUI\ERP\Products\Interfaces\Field
     */
    public function getField($fieldId)
    {
        return $this->Product->getField($fieldId);
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        $fields = $this->Product->getFields();
        $fields = array_filter($fields, function ($Field) {
            /* @var $Field QUI\ERP\Products\Interfaces\Field */
            return $Field->isPublic();
        });

        return $fields;
    }

    /**
     * Return the main catgory
     *
     * @return QUI\ERP\Products\Category\Category
     */
    public function getCategory()
    {
        return $this->Product->getCategory();
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->Product->getCategories();
    }

    /**
     * Return the product image
     *
     * @return QUI\Projects\Media\Image
     */
    public function getImage()
    {
        return $this->Product->getImage();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->Product->getUrl();
    }
}
