<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_addProducts
 */

use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Products;

/**
 * Add products to the category
 *
 * @param string|integer $categoryId - Category ID
 * @param string $productIds - JSON Array, Product Ids
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_categories_addProducts',
    function ($categoryId, $productIds) {
        $Category = Categories::getCategory($categoryId);
        $productIds = json_decode($productIds, true);
        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getProduct($productId);
                $Product->addCategory($Category);
                $Product->save();
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            throw $ExceptionStack;
        }
    },
    ['categoryId', 'productIds'],
    'Permission::checkAdminUser'
);
