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
     * Doublicate entries allowed?
     * Default = false
     * @var bool
     */
    public $duplicate = false;

    /**
     * ProductList constructor.
     *
     * @param array $params - optional, list settings
     */
    public function __construct($params = array())
    {
        if (isset($params['duplicate'])) {
            $this->duplicate = (boolean)$params['duplicate'];
        }
    }

    /**
     * Calculate the prices in the list
     */
    public function calc()
    {
        QUI\ERP\Products\Utils\Calc::calcProductList($this);
    }

    /**
     * Return the products
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add a product to the list
     * @param QUI\ERP\Products\Interfaces\Product $Product
     */
    public function addProduct(QUI\ERP\Products\Interfaces\Product $Product)
    {
        // only UniqueProduct can be calculated

        /* @var $Product QUI\ERP\Products\Product\Model */
        if ($Product instanceof QUI\ERP\Products\Product\Model) {
            $Product = $Product->createUniqueProduct();
        }

        if (!($Product instanceof UniqueProduct)) {
            return;
        }

        if ($this->duplicate) {
            $this->products[] = $Product;
            return;
        }

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
            $attributes = $Product->getAttributes();
            $fields     = $Product->getFields();

            $attributes['fields'] = array();

            /* @var $Field QUI\ERP\Products\Interfaces\Field */
            foreach ($fields as $Field) {
                $attributes['fields'][] = $Field->getAttributes();
            }

            $list[] = $attributes;
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
