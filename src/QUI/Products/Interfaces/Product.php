<?php

/**
 * This file contains QUI\Products\Interfaces\Product
 */
namespace QUI\Products\Interfaces;

use QUI\Products\Field;
use QUI\Products\Price;

/**
 * Interface Product
 * @package QUI\Products
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
     * Return all fields
     *
     * @return array
     */
    public function getFields();

    /**
     * Return the field attribute / value of the product
     *
     * @param Field $Field
     * @return array
     */
    public function getFieldValue(Field $Field);

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

    /**
     * Set an attribute of the product
     *
     * @param string $name - name of the attribute
     * @param mixed $value - value
     */
    public function setAttribute($name, $value);
}
