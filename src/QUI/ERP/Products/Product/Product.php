<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;

/**
 * Class Product
 * - Controller
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Product extends Model implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * Add a field to the product
     *
     * @param Field $Field
     */
    public function addField(Field $Field)
    {
        $this->fields[$Field->getId()] = $Field;
    }

    /**
     * Add the product to a category
     *
     * @param Category $Category
     */
    public function addCategory(Category $Category)
    {
        $this->categories[$Category->getId()] = $Category;
    }

    /**
     * Set the main category
     *
     * @param Category|integer $Category
     * @throws QUI\Exception
     */
    public function setMainCategory($Category)
    {
        if (!Categories::isCategory($Category)) {
            $Category = Categories::getCategory($Category);
        }

        $this->Category = $Category;
    }

    /**
     * Return
     */
    public function getPriceFactors()
    {
        $result = array();

        return $result;
    }
}
