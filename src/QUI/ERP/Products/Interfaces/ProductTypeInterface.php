<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\ProductTypeInterface
 */

namespace QUI\ERP\Products\Interfaces;

/**
 * Interface ProductTypeInterface
 *
 * @package QUI\ERP\Products
 */
interface ProductTypeInterface
{
    /**
     * Return the title of the product type
     *
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeTitle($Locale = null);

    /**
     * Return the description of the product type
     *
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeDescription($Locale = null);

    /**
     * Return the backend panel
     *
     * @return string
     */
    public static function getTypeBackendPanel();

    /**
     * @return mixed
     */
    public static function isTypeSelectable();
}
