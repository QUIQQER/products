<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\Product
 */
namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Interfaces\Field;
use QUI\ERP\Products\Utils\Price;

/**
 * Interface Product
 * @package QUI\ERP\Products
 */
interface Product
{
    /**
     * Return the Product-ID
     *
     * @return integer
     */
    public function getId();

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
     * Return all fields
     *
     * @return array
     */
    public function getFields();

    /**
     * Return the field
     *
     * @param integer $fieldId
     * @return Field
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
     * Return the price object of the product
     *
     * @return Price
     */
    public function getPrice();

    /**
     * Return an attribute of the product
     *
     * @param string $name - name of the attribute
     * @return mixed
     */
    public function getAttribute($name);
}
