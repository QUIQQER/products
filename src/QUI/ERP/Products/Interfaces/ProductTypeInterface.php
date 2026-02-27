<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\ProductTypeInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\Locale;

/**
 * Interface ProductTypeInterface
 */
interface ProductTypeInterface
{
    /**
     * Return the title of the product type
     *
     * @param Locale|null $Locale
     * @return string
     */
    public static function getTypeTitle(null | Locale $Locale = null): string;

    /**
     * Return the description of the product type
     *
     * @param Locale|null $Locale
     * @return string
     */
    public static function getTypeDescription(null | Locale $Locale = null): string;

    /**
     * Return the backend panel
     *
     * @return string
     */
    public static function getTypeBackendPanel(): string;

    /**
     * @return bool
     */
    public static function isTypeSelectable(): bool;
}
