<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\Product
 */
namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Interfaces\Field;
use QUI\ERP\Products\Price;

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
     * @param \QUI\ERP\Products\Interfaces\Field $Field
     */
    public function addField(Field $Field);

    /**
     * Set an attribute of the product
     *
     * @param string $name - name of the attribute
     * @param mixed $value - value
     */
    public function setAttribute($name, $value);
}
