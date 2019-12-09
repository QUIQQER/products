<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\ProductInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Category\Category;
use QUI\ERP\Money\Price;

/**
 * Interface Product
 * @package QUI\ERP\Products
 */
interface ProductInterface
{
    /**
     * Return the Product-ID
     *
     * @return integer
     */
    public function getId();

    /**
     * Return all attributes of the product
     *
     * @return mixed
     */
    public function getAttributes();

    /**
     * Return the translated title
     *
     * @param bool $Locale
     * @return string
     */
    public function getTitle($Locale = false);

    /**
     * Return the translated description
     *
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false);

    /**
     * Return the translated content
     *
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false);

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields();

    /**
     * Return the field
     *
     * @param integer $fieldId
     * @return FieldInterface
     */
    public function getField($fieldId);

    /**
     * Return the field attribute / value of the product
     *
     * @param integer $fieldId
     * @return mixed
     */
    public function getFieldValue($fieldId);

    /**
     * Return all fields from the wanted type
     *
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type);

    /**
     * Return the main product image
     *
     * @return \QUI\Projects\Media\Image
     * @throws \QUI\Exception
     */
    public function getImage();

    /**
     * Return the price object of the product
     *
     * @return Price
     */
    public function getPrice();

    /**
     * Return the lowest possible price
     *
     * @return Price
     */
    public function getMinimumPrice();

    /**
     * Return the highest possible price
     *
     * @return Price
     */
    public function getMaximumPrice();

    /**
     * Return the maximum quantity for this product
     *
     * @return bool|integer|float
     */
    public function getMaximumQuantity();

    /**
     * Return an attribute of the product
     *
     * @param string $name - name of the attribute
     * @return mixed
     */
    public function getAttribute($name);

    /**
     * Return the main category
     *
     * @return Category|null
     */
    public function getCategory();

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories();

    /**
     * Has the product an offer price
     *
     * @return bool
     */
    public function hasOfferPrice();

    /**
     * Return the original price, not the offer price
     *
     * @return false|UniqueFieldInterface
     */
    public function getOriginalPrice();
}
