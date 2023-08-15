<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */

namespace QUI\ERP\Products;

use QUI\Controls\Sitemap\Item;
use QUI\Controls\Sitemap\Map;
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
     * @param \QUI\Controls\Sitemap\Map $Map
     */
    public static function addMenuItems(Map $Map)
    {
        $Products = $Map->getChildrenByName('products');

        if ($Products === null) {
            $Products = new Item([
                'icon' => 'fa fa-shopping-bag',
                'name' => 'products',
                'text' => ['quiqqer/products', 'erp.panel.products.text'],
                'opened' => true,
                'priority' => 2
            ]);

            $Map->appendChild($Products);
        }

        $Products->appendChild(
            new Item([
                'icon' => 'fa fa-shopping-bag',
                'name' => 'products-products',
                'text' => ['quiqqer/products', 'menu.erp.products.products.title'],
                'require' => 'package/quiqqer/products/bin/controls/products/Panel'
            ])
        );

        $Products->appendChild(
            new Item([
                'icon' => 'fa fa-sitemap',
                'name' => 'products-categories',
                'text' => ['quiqqer/products', 'menu.erp.products.categories.title'],
                'require' => 'package/quiqqer/products/bin/controls/categories/Panel'
            ])
        );
    }

    /**
     * @return array
     */
    public static function getNumberRanges()
    {
        return [
            new NumberRange()
        ];
    }
}
