<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getFilters
 */

/**
 * Return the fields from a product list
 *
 * @param string $project - Project data
 * @param string|int $id - Site ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getFilters',
    function ($project, $siteId) {
        try {
            $Project = QUI::getProjectManager()->decode($project);
            $Site    = $Project->get($siteId);

            $Site->load();

            $ProductList = new QUI\ERP\Products\Controls\Category\ProductList(array(
                'Site'                 => $Site,
                'categoryId'           => $Site->getAttribute('quiqqer.products.settings.categoryId'),
                'hideEmptyProductList' => true,
                'categoryStartNumber'  => $Site->getAttribute('quiqqer.products.settings.categoryStartNumber'),
                'categoryView'         => $Site->getAttribute('quiqqer.products.settings.categoryDisplay')
            ));

            $result = $ProductList->createFilter();

            QUI::getMessagesHandler()->clear();

            return $result;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return '';
    },
    array('project', 'siteId')
);
