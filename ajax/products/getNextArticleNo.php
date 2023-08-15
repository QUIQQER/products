<?php

/**
 * Get the next auto-generated article no.
 *
 * @return string
 */

use QUI\ERP\Products\Handler\Products;

QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_getNextArticleNo',
    function () {
        try {
            $Conf = QUI::getPackage('quiqqer/products')->getConfig();

            if (!empty($Conf->get('autoArticleNos', 'generate'))) {
                return Products::generateArticleNo();
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return false;
    },
    [],
    'Permission::checkAdminUser'
);
