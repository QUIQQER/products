<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\ProductTypeInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Product\Model;

/**
 * Interface ProductTypeInterface
 * @package QUI\ERP\Products
 */
interface ProductTypeInterface
{
    /**
     * ProductTypeInterface constructor.
     *
     * @param Model $Product
     */
    public function __construct(Model $Product);

    /**
     * Return the title of the product type
     *
     * @param null $Locale
     * @return mixed
     */
    public static function getTitle($Locale = null);

    /**
     * Return the description of the product type
     *
     * @param null $Locale
     * @return mixed
     */
    public static function getDescription($Locale = null);
}
