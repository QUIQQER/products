<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */

namespace QUI\ERP\Products;

use QUI\ERP\Api\AbstractErpProvider;

/**
 * Class ErpProvider
 * Produkte ERP Provider -> erweitert das ERP Shop Panel
 *
 * @package QUI\ERP\Products
 */
class ErpProvider extends AbstractErpProvider
{
    /**
     * @return array
     */
    public static function getMenuItems()
    {
        $menu = array();

        $menu[] = array(
            'icon'  => 'fa fa-shopping-bag',
            'text'  => array('quiqqer/products', 'menu.erp.products.products.title'),
            'panel' => 'package/quiqqer/products/bin/controls/products/Panel'
        );

        $menu[] = array(
            'icon'  => 'fa fa-sitemap',
            'text'  => array('quiqqer/products', 'menu.erp.products.categories.title'),
            'panel' => 'package/quiqqer/products/bin/controls/categories/Panel'
        );

        return $menu;
    }
}
