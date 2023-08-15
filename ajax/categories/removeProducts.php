<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_removeProducts
 */

use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Products;

/**
 * Remove products from the category
 *
 * @param string|integer $categoryId - Category ID
 * @param string $productIds - JSON Array, Product Ids
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_removeProducts',
    function ($categoryId, $productIds) {
        $Category = Categories::getCategory($categoryId);
        $productIds = \json_decode($productIds, true);

        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getProduct($productId);
                $Product->removeCategory($Category->getId());
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
