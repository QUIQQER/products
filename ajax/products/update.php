<?php

/**
 * This file contains package_quiqqer_products_ajax_products_update
 */

use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

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
        $Products = new QUI\ERP\Products\Handler\Products();
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $Categories = new QUI\ERP\Products\Handler\Categories();
        $Product = $Products->getProduct($productId);

        $categories = json_decode($categories, true);
        $fields = json_decode($fields, true);

        // fields
        foreach ($fields as $fieldId => $field) {
            try {
                $fieldId = (int)str_replace('field-', '', $fieldId);
                $Field = $Fields->getField($fieldId);
            } catch (QUI\Exception) {
                QUI\System\Log::addNotice('Field not found #' . $fieldId);
                continue;
            }

            try {
                $ProductField = $Product->getField($Field->getId());

                // wenn es ein feld ist, welches vom benutzer / käufer ausgefüllt werden muss,
                // werden keine values gesetzt
                if ($ProductField->isCustomField()) {
                    continue;
                }

                // pcsg-projects/demo-shop/-/issues/9#note_152341
                if (
                    $Product instanceof VariantParent
                    && $ProductField instanceof QUI\ERP\Products\Field\Types\AttributeGroup
                ) {
                    $editable = $Product->getAttribute('editableVariantFields');

                    if (is_array($editable) && in_array($ProductField->getId(), $editable)) {
                        $ProductField->setValue($field);
                        continue;
                    }
                }

                if (
                    $ProductField instanceof QUI\ERP\Products\Field\Types\AttributeGroup
                    && ($Product instanceof VariantParent || $Product instanceof VariantChild)
                ) {
                    if ($ProductField->getOption('exclude_from_variant_generation')) {
                        $ProductField->setValue($field);
                    }

                    continue;
                }

                $ProductField->setValue($field);
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
                if ($Exception->getCode() === 1002) {
                    continue;
                }

                QUI\System\Log::addNotice(
                    $Exception->getMessage(),
                    [
                        'id' => $Field->getId(),
                        'title' => $Field->getTitle(),
                        'data' => $field
                    ]
                );

                throw $Exception;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addNotice(
                    $Exception->getMessage(),
                    [
                        'id' => $Field->getId(),
                        'title' => $Field->getTitle(),
                        'data' => $field
                    ]
                );

                throw $Exception;
            }
        }

        // categories
        $Product->clearCategories();

        foreach ($categories as $category) {
            try {
                $Category = $Categories->getCategory($category);
                $Product->addCategory($Category);
            } catch (QUI\Exception) {
            }
        }

        try {
            $Product->setMainCategory($categoryId);
        } catch (QUI\Exception) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/products',
                    'message.set.maincategory.error'
                )
            );
        }

        try {
            $Product->userSave();
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addAttention($Exception->getMessage());
            return;
        } catch (Exception $Exception) { // @phpstan-ignore-line
            QUI\System\Log::writeException($Exception);

            QUI\System\Log::addError(
                'AJAX :: package_quiqqer_products_ajax_products_update -> ' . $Exception->getMessage()
            );

            throw $Exception;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/products',
                'product.successfully.saved'
            )
        );
    },
    ['productId', 'categories', 'categoryId', 'fields'],
    'Permission::checkAdminUser'
);
