<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_getSites
 */

/**
 * Return all sites from a category
 *
 * @param string $categoryId - Category-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_getSites',
    function ($categoryId) {
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Category   = $Categories->getCategory($categoryId);
        $sites      = $Category->getSites();
        $result     = array();

        /* @var $Site \QUI\Projects\Site */
        foreach ($sites as $Site) {
            $result[] = array(
                'project' => $Site->getProject()->getName(),
                'lang'    => $Site->getProject()->getLang(),
                'id'      => $Site->getId()
            );
        }

        return $result;
    },
    array('categoryId'),
    'Permission::checkAdminUser'
);
