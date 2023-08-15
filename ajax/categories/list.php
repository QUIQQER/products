<?php

/**
 * Returns category list for a grid
 *
 * @param string $params - JSON query params
 * @return array
 */

use QUI\ERP\Products\Handler\Categories;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_list',
    function ($params) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Grid = new QUI\Utils\Grid();
        $params = json_decode($params, true);
        $query = $Grid->parseDBParams($params);

        if (!empty($params['where'])) {
            $query['where'] = $params['where'];
        }

        $categoryIds = $Categories::getCategoryIds($query);

        $Locale = QUI::getLocale();
        $result = [];

        foreach ($categoryIds as $categoryId) {
            $Category = Categories::getCategory($categoryId);

            $result[] = [
                'id' => $Category->getId(),
                'title' => $Category->getTitle($Locale),
                'description' => $Category->getDescription($Locale),
                'path' => $Category->getPath($Locale)
            ];
        }

        if (!empty($query['where'])) {
            $count = $Categories->countCategories([
                'where' => $query['where']
            ]);
        } else {
            $count = $Categories->countCategories();
        }

        return $Grid->parseResult($result, $count);
    },
    ['params'],
    'Permission::checkAdminUser'
);
