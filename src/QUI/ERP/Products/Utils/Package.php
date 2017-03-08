<?php

/**
 * This file contains QUI\ERP\Products\Utils\Package
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Permissions\Permission;

/**
 * Class Package
 *
 * Package Helper methods
 */
class Package
{
    const PACKAGE = 'quiqqer/products';

    /**
     * @var null
     */
    static protected $hidePrice = null;

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

    /**
     * Hide price display?
     *
     * @return bool|int
     */
    public static function hidePrice()
    {
        // Wenn in Session der Preis versteckt werden soll
        // Dann hat dies Vorrang
        if (QUI::getSession()->get('QUIQQER_PRODUCTS_HIDE_PRICE') == 1) {
            return true;
        }

        if (!is_null(self::$hidePrice)) {
            return self::$hidePrice;
        }

        $Package = QUI::getPackage('quiqqer/products');
        $Config  = $Package->getConfig();
        $User    = QUI::getUserBySession();

        self::$hidePrice = (int)$Config->get('products', 'hidePrices');

        if ($User->getId() && Permission::hasPermission('product.view.prices')) {
            self::$hidePrice = false;
        }

        return self::$hidePrice;
    }
}
