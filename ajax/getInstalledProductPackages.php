<?php

/**
 * Get list of all packages that belong to the quiqqer/products ecosystem
 * but are not necessarily required.
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_getInstalledProductPackages',
    function () {
        $packages = [
            'quiqqer/productstags' => false,
            'quiqqer/productsimportexport' => false
        ];

        foreach ($packages as $pkg => $value) {
            try {
                QUI::getPackage($pkg);
                $packages[$pkg] = true;
            } catch (Exception) {
                // nothing, package is not installed
            }
        }

        return $packages;
    },
    [],
    'Permission::checkAdminUser'
);
