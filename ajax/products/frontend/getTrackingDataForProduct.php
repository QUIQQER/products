<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProduct
 */

use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Return the product data for tracking
 *
 * @param string $productId - ID of a product
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getTrackingDataForProduct',
    function ($productId) {
        $Locale = QUI::getLocale();
        $Product = Products::getNewProductInstance($productId);

        // categories
        $Category = $Product->getCategory();
        $categories = $Product->getCategories();

        $category = '';
        $categoryId = '';

        if ($Category) {
            $category = $Category->getTitle($Locale);
            $categoryId = $Category->getId();
        }

        $categories = array_map(function ($Category) use ($Locale) {
            /* @var $Category Category */
            return [
                'id' => $Category->getId(),
                'title' => $Category->getTitle($Locale)
            ];
        }, $categories);

        // price
        $price = 0;

        if (!QUI\ERP\Products\Utils\Package::hidePrice()) {
            $price = $Product->getPrice(QUI::getUserBySession())->getPrice();
        }

        return [
            'id' => $Product->getId(),
            'category' => $category,
            'categoryId' => $categoryId,
            'categories' => $categories,
            'title' => $Product->getTitle($Locale),
            'productNo' => $Product->getField(Fields::FIELD_PRODUCT_NO)->getValue(),
            'price' => $price
        ];
    },
    ['productId']
);
