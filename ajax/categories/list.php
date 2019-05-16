<?php

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

        $L = QUI::getLocale();

        foreach ($categoryIds as $categoryId) {
            $description = '';

            if ($L->exists('quiqqer/products', 'products.category.'.$categoryId.'.description')) {
                $description = $L->get('quiqqer/products', 'products.category.'.$categoryId.'.description');
            }

            $result[] = [
                'id'          => $categoryId,
                'title'       => $L->get('quiqqer/products', 'products.category.'.$categoryId.'.title'),
                'description' => $description
            ];
        }

//        \usort($result, function ($a, $b) {
//            return $a['title'] > $b['title'];
//        });

        return $Grid->parseResult($result, $Categories->countCategories());
    },
    ['params'],
    'Permission::checkAdminUser'
);
