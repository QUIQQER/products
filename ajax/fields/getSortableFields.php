<?php

/**
 * Returns all sortable fields
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getSortableFields',
    function () {
        return QUI\ERP\Products\Utils\Sortables::getFieldSettings();
    },
    false,
    'Permission::checkAdminUser'
);
