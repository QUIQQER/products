<?php

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Exception;
use QUI\Projects\Site;

use function array_filter;
use function array_flip;
use function array_map;
use function array_unshift;
use function explode;
use function trim;
use function usort;

class Sortables
{
    /**
     * @param Site $Site
     * @return array|bool
     * @throws Exception
     */
    public static function getSortableFieldsForSite(QUI\Projects\Site $Site): array|bool
    {
        $useOwnSorting = $Site->getAttribute('quiqqer.products.settings.useOwnSorting');

        if ($useOwnSorting) {
            $fields = $Site->getAttribute('quiqqer.products.settings.availableSorting');
            $fields = trim($fields);

            if (!empty($fields)) {
                $fields = explode(',', $fields);
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
     * @param Site $Site
     * @return array
     * @throws Exception
     */
    public static function getFieldSettingsForSite(QUI\Projects\Site $Site): array
    {
        $useOwnSorting = $Site->getAttribute('quiqqer.products.settings.useOwnSorting');
        $result = self::getFieldSettings();

        if (!$useOwnSorting) {
            $fields = self::getDefaultFields();
        } else {
            $fields = $Site->getAttribute('quiqqer.products.settings.availableSorting');

            if ($fields !== false) {
                $fields = trim($fields, ',');

                if (!empty($fields)) {
                    $fields = explode(',', trim($fields, ','));
                }

                if (!empty($fields)) {
                    $fields = array_flip($fields);
                }

                if (!is_array($fields)) {
                    $fields = [];
                }
            } else {
                $fields = self::getDefaultFields();
            }
        }

        foreach ($result as $key => $value) {
            $fieldId = $value['id'];

            $result[$key]['sorting'] = isset($fields[$fieldId]);
        }

        return $result;
    }

    /**
     * @return string[]
     * @throws QUI\Exception
     */
    public static function getDefaultFields(): array
    {
        $Package = QUI::getPackage('quiqqer/products')->getConfig();
        $sortingFields = $Package->getValue('products', 'sortFields');

        return explode(',', $sortingFields);
    }

    /**
     * @return array[]
     *
     * @throws QUI\Exception
     */
    public static function getFieldSettings(): array
    {
        // config
        $Package = QUI::getPackage('quiqqer/products')->getConfig();
        $sortingFields = $Package->getValue('products', 'sortFields');
        $sortingFields = explode(',', $sortingFields);
        $sortingFields = array_flip($sortingFields);

        // field sortables
        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFieldIds([
            'where' => [
                'search_type' => [
                    'type' => 'NOT',
                    'value' => null
                ]
            ]
        ]);

        $result = array_map(function ($field) use ($Fields, $sortingFields) {
            try {
                $Field = $Fields->getField($field['id']);
            } catch (QUI\Exception) {
                return null;
            }

            return [
                'idDisplay' => $Field->getId(),
                'id' => 'F' . $Field->getId(),
                'title' => $Field->getTitle(),
                'sorting' => isset($sortingFields['F' . $Field->getId()])
            ];
        }, $fields);

        $result = array_filter($result);

        usort($result, function ($a, $b) {
            if ($a['idDisplay'] === $b['idDisplay']) {
                return 0;
            }

            return $a['idDisplay'] < $b['idDisplay'] ? -1 : 1;
        });

        // add system sortables to the top
        $special = ['c_date', 'e_date'];

        foreach ($special as $s) {
            array_unshift($result, [
                'id' => 'S' . $s,
                'idDisplay' => $s,
                'title' => QUI::getLocale()->get('quiqqer/products', 'sortable.' . $s),
                'sorting' => isset($sortingFields['S' . $s])
            ]);
        }

        return $result;
    }
}
