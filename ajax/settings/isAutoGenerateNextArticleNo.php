<?php

use QUI\ERP\Products\Handler\Products;

/**
 * Check if product article nos. are auto-generated
 *
 * @return bool
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_isAutoGenerateNextArticleNo',
    function () {
        return Products::isAutoGenerateArticleNo();
    },
    [],
    'Permission::checkAdminUser'
);
