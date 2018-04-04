<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_createProjectSite
 */

/**
 * Create a project site and a category
 *
 * @param string $project - JSON project
 * @param string $siteId - Project parent site ID
 * @param string $title - New Title
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_createProjectSite',
    function ($project, $siteId, $title, $createCategory, $parentCategory) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = $Project->get($siteId);

        $newChildId = $Site->createChild([
            'name'  => QUI\Projects\Site\Utils::clearUrl($title, $Project),
            'title' => $title
        ]);

        if ((int)$createCategory == 0) {
            return $newChildId;
        }

        $Category = QUI\ERP\Products\Handler\Categories::createCategory($parentCategory, $title);

        $NewChild = $Project->get($newChildId);
        $NewChild->setAttribute('quiqqer.products.settings.categoryId', $Category->getId());
        $NewChild->save();

        return $NewChild->getId();
    },
    ['project', 'siteId', 'title', 'createCategory', 'parentCategory'],
    'Permission::checkAdminUser'
);
