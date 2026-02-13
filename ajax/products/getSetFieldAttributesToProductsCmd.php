<?php

/**
 * Get console command to set field attributes to all products
 *
 * @return string
 */

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_getSetFieldAttributesToProductsCmd',
    function ($fieldId) {
        $cmsDir = QUI::conf('globals', 'cms_dir');

        return $cmsDir . 'console products:set-field-attributes-to-products --fieldId=' . $fieldId;
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
