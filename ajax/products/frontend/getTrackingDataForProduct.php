<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProduct
 */

use QUI\ERP\Products\Product\Product;
use QUI\ERP\Products\Handler\Fields;

/**
 * Return the product data for tracking
 *
 * @param string $productId - ID of a product
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getTrackingDataForProduct',
    function ($productId) {
        $Locale  = QUI::getLocale();
        $Product = new Product($productId);

        // categories
        $Category   = $Product->getCategory();
        $categories = $Product->getCategories();

        $category   = '';
        $categoryId = '';

        if ($Category) {
            $category   = $Category->getTitle($Locale);
            $categoryId = $Category->getId();
        }

        $categories = array_map(function ($Category) use ($Locale) {
            /* @var $Category \QUI\ERP\Products\Category\Category */
            return array(
                'id'    => $Category->getId(),
                'title' => $Category->getTitle($Locale)
            );
        }, $categories);

        // price
        $price = 0;

        if (QUI\ERP\Products\Utils\Package::hidePrice() !== false) {
            $price = $Product->getPrice(QUI::getUserBySession())->getPrice();
        }

        return array(
            'id'         => $Product->getId(),
            'category'   => $category,
            'categoryId' => $categoryId,
            'categories' => $categories,
            'title'      => $Product->getTitle($Locale),
            'productNo'  => $Product->getField(Fields::FIELD_PRODUCT_NO)->getValue(),
            'price'      => $price
        );
    },
    array('productId')
);
