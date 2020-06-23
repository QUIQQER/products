<?php

/**
 * Returns all sortable fields
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getSortableFieldsForSite',
    function ($siteId, $projectData) {
        $Project = QUI::getProjectManager()->decode($projectData);
        $Site    = $Project->get($siteId);

        return QUI\ERP\Products\Utils\Sortables::getSortableFieldsForSite($Site);
    },
    ['siteId', 'projectData'],
    'Permission::checkAdminUser'
);
