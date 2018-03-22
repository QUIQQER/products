<?php

/**
 * This file contains package_quiqqer_products_ajax_search_clearSearchCache
 */

use \QUI\ERP\Products\Search\Cache;

/**
 * Clear the search cache for all cached search values
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_clearSearchCache',
    function () {
        // TODO: permission?
        Cache::clear();
    },
    [],
    'Permission::checkAdminUser'
);
