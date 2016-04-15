<?php

/**
 * This file contains package_quiqqer_products_ajax_categories_update
 */

/**
 * Update category
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_categories_update',
    function ($categoryId, $params, $updateProductFields) {
        if (!isset($updateProductFields)) {
            $updateProductFields = false;
        }

        $updateProductFields = (bool)$updateProductFields;

        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Fields     = new QUI\ERP\Products\Handler\Fields();
        $Category   = $Categories->getCategory($categoryId);
        $params     = json_decode($params, true);
        
        if (isset($params['fields'])) {
            $Category->clearFields();

            foreach ($params['fields'] as $fieldData) {
                try {
                    $Field = $Fields->getField($fieldData['id']);

                    $Field->setAttribute('publicStatus', $fieldData['publicStatus']);
                    $Field->setAttribute('searchStatus', $fieldData['searchStatus']);

                    $Category->addField($Field);

                } catch (QUI\Exception $Exception) {
                }
            }
        }

        $Category->setAttributes($params);
        $Category->save();

        if ($updateProductFields) {
            $Category->setFieldsToAllProducts();
        }
    },
    array('categoryId', 'params', 'updateProductFields'),
    'Permission::checkAdminUser'
);
