<?php

/**
 * This file contains package_quiqqer_products_ajax_products_getParentId
 */

/**
 * Create a new product
 *
 * @return array
 */
QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_products_getParentFolder',
    function () {
        $Package = QUI::getPackage('quiqqer/products');
        $Config = $Package->getConfig();

        try {
            $folder = $Config->get('products', 'folder');
            $Folder = QUI\Projects\Media\Utils::getMediaItemByUrl($folder);
            $Project = $Folder->getProject();

            return [
                'project' => $Project->getName(),
                'id' => $Folder->getId()
            ];
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return false;
        }
    },
    false,
    'Permission::checkAdminUser'
);
