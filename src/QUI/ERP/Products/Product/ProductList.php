<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class ProductList
 * @package QUI\ERP\Products\Product
 */
class ProductList
{
    /**
     * @var array
     */
    protected $products = array();

    /**
     * Add a product to the list
     * @param QUI\ERP\Products\Interfaces\Product $Product
     */
    public function addProduct(QUI\ERP\Products\Interfaces\Product $Product)
    {
        $this->products[$Product->getId()] = $Product;
    }

    /**
     * Clears the list
     */
    public function clear()
    {
        $this->products = array();
    }

    /**
     * Return the products as array list
     *
     * @return array
     */
    public function toArray()
    {
        $list = array();

        /* @var $Product Product */
        foreach ($this->products as $Product) {
            $list[] = $Product->getAttributes();
        }

        return $list;
    }

    /**
     * Return the products as json notation
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }
}
