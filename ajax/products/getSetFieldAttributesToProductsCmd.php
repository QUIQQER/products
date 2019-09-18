<?php

use QUI\ERP\Products\Handler\Products;

/**
 * Get console command to set field attributes to all products
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getSetFieldAttributesToProductsCmd',
    function ($fieldId) {
        $cmsDir = QUI::conf('globals', 'cms_dir');

        return 'php '.$cmsDir.'quiqqer.php --username=USERNAME --password=PASSWORD'
               .' --tool=products:set-field-attributes-to-products'
               .' --fieldId='.$fieldId;
    },
    ['fieldId'],
    'Permission::checkAdminUser'
);
