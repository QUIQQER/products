<?php

/**
 * Returns all public fields
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_getPublicFields',
    function () {
        $cacheName = \QUI\ERP\Products\Handler\Cache::getBasicCachePath().'fields/publicFields';

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
            // nothing
        }

        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFieldIds([
            'where' => [
                'publicField' => 1
            ]
        ]);

        $result = \array_map(function ($field) use ($Fields) {
            return $Fields->getField($field['id'])->getAttributes();
        }, $fields);

        QUI\Cache\Manager::set($cacheName, $result);

        return $result;
    },
    false
);
