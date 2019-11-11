<?php

use QUI\ERP\Products\Handler\Categories;

/**
 * Returns category list for a grid
 *
 * @param string $params - JSON query params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_list',
    function ($params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Grid       = new QUI\Utils\Grid();

        $categoryIds = $Categories::getCategoryIds(
            $Grid->parseDBParams(\json_decode($params, true))
        );

        $Locale = QUI::getLocale();
        $result = [];

        foreach ($categoryIds as $categoryId) {
            $Category = Categories::getCategory($categoryId);

            $result[] = [
                'id'          => $Category->getId(),
                'title'       => $Category->getTitle($Locale),
                'description' => $Category->getDescription($Locale),
                'path'        => $Category->getPath($Locale)
            ];
        }

        return $Grid->parseResult($result, $Categories->countCategories());
    },
    ['params'],
    'Permission::checkAdminUser'
);
