<?php


/**
 * This file contains QUI\ERP\Products\Utils\Fields
 */

namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class FieldSortCache
 *
 * @package QUI\ERP\Products\Utils
 */
class FieldSortCache
{
    /**
     * @var array
     */
    protected static $fieldsCache = [];

    /**
     * @param array $fields - FieldInterface[]
     * @param string $sort
     *
     * @return array|false
     */
    public static function getFieldCache($fields, $sort)
    {
        /* @param QUI\ERP\Products\Field\Field $Field */
        $fieldCache  = [];
        $fieldIdTemp = [];

        foreach ($fields as $Field) {
            $fieldCache[]                 = $Field->getId(); // collect ids
            $fieldIdTemp[$Field->getId()] = $Field;          // collect fields by ids
        }

        \sort($fieldCache);
        $fieldCache = \implode('-', $fieldCache);

        if (!empty(self::$fieldsCache[$sort][$fieldCache])) {
            $fieldIds = self::$fieldsCache[$sort][$fieldCache];
            $sorted   = [];

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
    public static function setFieldCache($fields, $sort, $cache)
    {
        /* @param QUI\ERP\Products\Field\Field $Field */
        $fieldCache = [];

        foreach ($fields as $Field) {
            $fieldCache[] = $Field->getId(); // collect ids
        }

        \sort($fieldCache);
        $fieldCache = \implode('-', $fieldCache);

        self::$fieldsCache[$sort][$fieldCache] = $cache;
    }
}
