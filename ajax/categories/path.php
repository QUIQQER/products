<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_path
 */

/**
 * Return the category path
 *
 * @param string $params - JSON query params
 *
 * @return array
 */

use QUI\ERP\Products\Interfaces\CategoryInterface;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_path',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $result = [];

        $Category = $Categories->getCategory($categoryId);
        $result[] = $Category->getId();

        $Parent = $Category->getParent();

        while ($Parent) {
            /* @var $Parent CategoryInterface */
            try {
                $result[] = $Parent->getId();
                $Parent = $Parent->getParent();
            } catch (QUI\Exception) {
                break;
            }
        }

        return array_reverse($result);
    },
    ['categoryId'],
    'Permission::checkAdminUser'
);
