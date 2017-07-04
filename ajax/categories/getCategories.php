<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getCategories
 */

/**
 * Returns categories information
 *
 * @param string $categoryIds - JSON Array ids
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_getCategories',
    function ($categoryIds) {
        $Categories  = new QUI\ERP\Products\Handler\Categories();
        $result      = array();
        $categoryIds = json_decode($categoryIds, true);

        foreach ($categoryIds as $categoryId) {
            try {
                $Category = $Categories->getCategory($categoryId);
                $result[] = $Category->getAttributes();
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    },
    array('categoryIds'),
    false
);
