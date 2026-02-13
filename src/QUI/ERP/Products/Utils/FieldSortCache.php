<?php

/**
 * This file contains QUI\ERP\Products\Utils\Fields
 */

namespace QUI\ERP\Products\Utils;

use QUI;

use function implode;
use function sort;

/**
 * Class FieldSortCache
 */
class FieldSortCache
{
    /**
     * @var array
     */
    protected static array $fieldsCache = [];

    /**
     * @param array $fields - FieldInterface[]
     * @param string $sort
     *
     * @return array|false
     */
    public static function getFieldCache(array $fields, string $sort): bool|array
    {
        /* @param QUI\ERP\Products\Field\Field $Field */
        $fieldCache = [];
        $fieldIdTemp = [];

        foreach ($fields as $Field) {
            $fieldCache[] = $Field->getId(); // collect ids
            $fieldIdTemp[$Field->getId()] = $Field;          // collect fields by ids
        }

        sort($fieldCache);
        $fieldCache = implode('-', $fieldCache);

        if (!empty(self::$fieldsCache[$sort][$fieldCache])) {
            $fieldIds = self::$fieldsCache[$sort][$fieldCache];
            $sorted = [];

            foreach ($fieldIds as $fieldId) {
                $sorted[] = $fieldIdTemp[$fieldId];
            }

            return $sorted;
        }

        return false;
    }

    /**
     * Set field cache
     *
     * @param array $fields - FieldInterface[]
     * @param string $sort
     * @param array $cache
     */
    public static function setFieldCache(array $fields, string $sort, array $cache): void
    {
        /* @param QUI\ERP\Products\Field\Field $Field */
        $fieldCache = [];

        foreach ($fields as $Field) {
            $fieldCache[] = $Field->getId(); // collect ids
        }

        sort($fieldCache);
        $fieldCache = implode('-', $fieldCache);

        self::$fieldsCache[$sort][$fieldCache] = $cache;
    }
}
