<?php

namespace QUI\ERP\Products\Utils;

use QUI;

class Sortables
{
    /**
     * @param QUI\Projects\Site $Site
     * @return array
     */
    public static function getSortableFieldsForSite(QUI\Projects\Site $Site)
    {
        $useOwnSorting = $Site->getAttribute('quiqqer.products.settings.useOwnSorting');

        if ($useOwnSorting) {
            $fields = $Site->getAttribute('quiqqer.products.settings.availableSorting');
            $fields = \trim($fields);

            if (!empty($fields)) {
                $fields = \explode(',', $fields);
            }

            if (!is_array($fields)) {
                $fields = [];
            }
        } else {
            return self::getDefaultFields();
        }

        return $fields;
    }

    /**
     * @param QUI\Projects\Site $Site
     * @throws QUI\Exception
     */
    public static function getFieldSettingsForSite(QUI\Projects\Site $Site)
    {
        $useOwnSorting = $Site->getAttribute('quiqqer.products.settings.useOwnSorting');

        if (!$useOwnSorting) {
            return self::getDefaultFields();
        }

        $fields = $Site->getAttribute('quiqqer.products.settings.availableSorting');
        $result = self::getFieldSettings();

        if ($fields !== false) {
            $fields = \trim($fields, ',');

            if (!empty($fields)) {
                $fields = \explode(',', \trim($fields, ','));
            }

            if (!empty($fields)) {
                $fields = \array_flip($fields);
            }

            if (!is_array($fields)) {
                $fields = [];
            }
        } else {
            $fields = self::getDefaultFields();
        }

        foreach ($result as $key => $value) {
            $fieldId = $value['id'];

            $result[$key]['sorting'] = isset($fields[$fieldId]);
        }

        return $result;
    }

    /**
     * @return false|string[]
     * @throws QUI\Exception
     */
    public static function getDefaultFields()
    {
        $Package       = QUI::getPackage('quiqqer/products')->getConfig();
        $sortingFields = $Package->getValue('products', 'sortFields');
        $sortingFields = \explode(',', $sortingFields);

        return $sortingFields;
    }

    /**
     * @return array[]
     *
     * @throws QUI\Exception
     */
    public static function getFieldSettings()
    {
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
    }
}
