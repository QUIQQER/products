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
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $Category = $Categories->getCategory($categoryId);
        $params = json_decode($params, true);

        if (isset($params['fields'])) {
            $Category->clearFields();

            foreach ($params['fields'] as $fieldData) {
                try {
                    $Field = $Fields->getField($fieldData['id']);
                    $Category->addField($Field);
                } catch (QUI\Exception) {
                }
            }
        }

        if (!empty($params['custom_data']) && is_array($params['custom_data'])) {
            foreach ($params['custom_data'] as $k => $v) {
                switch ($k) {
                    // Only allow certain custom data keys
                    case 'priceFieldFactors':
                        break;

                    default:
                        continue 2;
                }

                $Category->setCustomDataEntry($k, $v);
            }
        }

        $Category->setAttributes($params);
        $Category->save();

        if ($updateProductFields) {
            $Category->setFieldsToAllProducts();
        }
    },
    ['categoryId', 'params', 'updateProductFields'],
    'Permission::checkAdminUser'
);
