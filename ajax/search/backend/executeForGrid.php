<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_executeForGrid
 */

use QUI\ERP\Products\Handler\Products;

/**
 * Get all fields that are available for search for a specific Site
 * Return teh result for grid
 *
 * @param array $searchData
 * @return array - product list
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_executeForGrid',
    function ($searchParams) {
        require_once 'execute.php';

        // products
        $result = QUI::$Ajax->callRequestFunction(
            'package_quiqqer_products_ajax_search_backend_execute',
            array('searchParams' => $searchParams)
        );

        $page     = 1;
        $result   = $result['result'];
        $products = array();

        foreach ($result as $pid) {
            try {
                $Product    = Products::getProduct((int)$pid);
                $products[] = $Product->getAttributes();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeRecursive($Exception, QUI\System\Log::LEVEL_ALERT);

                $products[] = array(
                    'id' => (int)$pid
                );
            }
        }

        // count
        $searchParams          = json_decode($searchParams, true);
        $searchParams['count'] = 1;

        if (isset($searchParams['sheet'])) {
            $page = (int)$searchParams['sheet'];
        }

        $count = QUI::$Ajax->callRequestFunction(
            'package_quiqqer_products_ajax_search_backend_execute',
            array('searchParams' => json_encode($searchParams))
        );

        return array(
            'data'  => $products,
            'total' => $count['result'],
            'page'  => $page
        );
    },
    array('searchParams'),
    'Permission::checkAdminUser'
);
