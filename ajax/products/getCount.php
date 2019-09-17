<?php

use QUI\ERP\Products\Handler\Products;

/**
 * Get total product count
 *
 * @return int
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getCount',
    function () {
        return Products::countProducts();
    },
    [],
    'Permission::checkAdminUser'
);
