<?php

/**
 * Returns all available extra field settings
 *
 * @return array
 */

use QUI\ERP\Products\Handler\Fields;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getFieldOptions',
    function ($fieldId) {
        try {
            return Fields::getField($fieldId)->getOptions();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
