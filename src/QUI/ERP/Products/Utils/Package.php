<?php

/**
 * This file contains QUI\ERP\Products\Utils\Package
 */
namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class Package
 *
 * Package Helper methods
 */
class Package
{
    const PACKAGE = 'quiqqer/products';

    /**
     * Return config
     *
     * @return QUI\Config
     */
    public static function getConfig()
    {
        return QUI::getPackage(self::PACKAGE)->getConfig();
    }

    /**
     * Return the categories database table name
     *
     * @return string
     */
    public static function getVarDir()
    {
        return QUI::getPackage(self::PACKAGE)->getVarDir();
    }
}
