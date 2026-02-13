<?php

/**
 * Returns all public fields
 *
 * @return array
 */

use QUI\ERP\Products\Handler\Cache;

QUI::getAjax()->registerFunction(
    'package_quiqqer_products_ajax_fields_getPublicFields',
    function () {
        $cacheName = Cache::getBasicCachePath() . 'fields/publicFields';

        try {
            return QUI\Cache\LongTermCache::get($cacheName);
        } catch (QUI\Exception) {
            // nothing
        }

        $Fields = new QUI\ERP\Products\Handler\Fields();
        $fields = $Fields->getFieldIds([
            'where' => [
                'publicField' => 1
            ]
        ]);

        $result = array_map(function ($field) use ($Fields) {
            return $Fields->getField($field['id'])->getAttributes();
        }, $fields);

        QUI\Cache\LongTermCache::set($cacheName, $result);

        return $result;
    },
    false
);
