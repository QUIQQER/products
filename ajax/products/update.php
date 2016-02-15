<?php

/**
 * This file contains package_quiqqer_products_ajax_products_update
 */

/**
 * Update a product
 *
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_update',
    function ($productId, $categories, $categoryId, $fields) {
        $Products   = new QUI\ERP\Products\Handler\Products();
        $Fields     = new QUI\ERP\Products\Handler\Fields();
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Product    = $Products->getProduct($productId);

        $categories = json_decode($categories, true);
        $fields     = json_decode($fields, true);

        // fields
        foreach ($fields as $fieldId => $field) {
            try {
                $fieldId = (int)str_replace('field-', '', $fieldId);
                $Field   = $Fields->getField($fieldId);
            } catch (QUI\Exception $Exception) {
                \QUI\System\Log::addNotice('Field not found #' . $fieldId);
                continue;
            }

            $Field->setValue($field);
            $Product->addField($Field);
        }

        // categories
        $Product->clearCategories();

        foreach ($categories as $category) {
            try {
                $Category = $Categories->getCategory($category);

                $Product->addCategory($Category);

            } catch (QUI\Exception $Exception) {
            }
        }

        try {
            $Product->setMainCategory($categoryId);
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/products',
                    'message.set.maincategory.error'
                )
            );
        }

        $Product->save();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/products',
                'product.successfully.saved'
            )
        );
    },
    array('productId', 'categories', 'categoryId', 'fields'),
    'Permission::checkAdminUser'
);
