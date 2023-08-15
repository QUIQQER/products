<?php

/**
 * This file contains package_quiqqer_products_ajax_search_backend_getProductSearchFieldsData
 */

use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Get all fields that are available for the product search at the backend
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_search_backend_getProductSearchFieldsData',
    function () {
        $BackEndSearch = SearchHandler::getBackendSearch();
        $fields = $BackEndSearch->getProductSearchFields();
        $results = [];

        foreach ($fields as $fieldId => $val) {
            if ($val) {
                $Field = Fields::getField($fieldId);
                $data = $Field->getAttributes();
                $data['title'] = $Field->getTitle();

                $results[] = $data;
            }
        }

        return $results;
    },
    [],
    'Permission::checkAdminUser'
);
