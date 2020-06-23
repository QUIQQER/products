<?php

/**
 * Returns all sortable fields
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getSortableFields',
    function () {
        // config
        $Package       = QUI::getPackage('quiqqer/products')->getConfig();
        $sortingFields = $Package->getValue('products', 'sortFields');
        $sortingFields = \explode(',', $sortingFields);
        $sortingFields = \array_flip($sortingFields);

        // system sortables

        // field sortables
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFieldIds([
            'where' => [
                'search_type' => [
                    'type'  => 'NOT',
                    'value' => null
                ]
            ]
        ]);

        $result = \array_map(function ($field) use ($Fields, $sortingFields) {
            try {
                $Field = $Fields->getField($field['id']);
            } catch (QUI\Exception $Exception) {
                return null;
            }

            return [
                'id'      => $Field->getId(),
                'title'   => $Field->getTitle(),
                'sorting' => isset($sortingFields[$Field->getId()])
            ];
        }, $fields);

        $result = \array_filter($result);

        \usort($result, function ($a, $b) {
            if ($a['id'] == $b['id']) {
                return 0;
            }

            return ($a['id'] < $b['id']) ? -1 : 1;
        });

        return $result;
    },
    false
);
