<?php

/**
 * Get vat entries that can be selected for automatic product price multiplier rounding.
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_getVatEntries',
    function () {
        return QUI\ERP\Tax\Utils::getAvailableTaxList();
    },
    [],
    ['Permission::checkAdminUser']
);
