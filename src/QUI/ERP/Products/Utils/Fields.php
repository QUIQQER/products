<?php

/**
 * This file contains QUI\ERP\Products\Utils\Fields
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Interfaces\FieldInterface;

/**
 * Class Fields
 *
 * @package QUI\ERP\Products
 */
class Fields
{
    /**
     * @param array $fields
     * @return array
     * @deprecated riesen quatsch
     *
     * @todo wer hat diese methode gebaut? ToJson = return string, wieso array?
     */
    public static function parseFieldsToJson($fields = [])
    {
        $result = [];

        foreach ($fields as $Field) {
            if (!self::isField($Field)) {
                continue;
            }

            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            try {
                self::validateField($Field);

                $result[] = $Field->toProductArray();
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * @param $fieldHash
     * @return array
     */
    public static function parseFieldHashToArray($fieldHash)
    {
        $result    = [];
        $fieldHash = \explode(';', \trim($fieldHash, ';'));

        foreach ($fieldHash as $key => $entry) {
            $entry    = \explode(':', $entry);
            $entry[0] = (int)$entry[0];

            $result[$entry[0]] = $entry[1];
        }

        return $result;
    }

    /**
     * Return all search hashes from one field hash
     *
     * @param string $hash
     * @return array
     */
    public static function getSearchHashesFromFieldHash($hash)
    {
        $hashes           = self::parseFieldHashToArray($hash);
        $foundEmptyValues = false;

        $hashes = \array_map(function ($entry) use (&$foundEmptyValues) {
            if ($entry === '') {
                $foundEmptyValues = true;

                return '*';
            }

            return $entry;
        }, $hashes);

        $searchHashes = [];

        foreach ($hashes as $fieldId => $value) {
            $clone = $hashes;

            if (!$foundEmptyValues) {
                $clone[$fieldId] = '*';
            }

            $searchHashes[self::generateFieldHashFromArray($clone)] = true;

            if (!$foundEmptyValues) {
                continue;
            }

            try {
                $Field   = FieldHandler::getField($fieldId);
                $options = $Field->getOptions();

                if (!isset($options['entries'])) {
                    continue;
                }

                foreach ($options['entries'] as $option) {
                    $clone[$fieldId] = $option['valueId'];
                    $generatedHash   = self::generateFieldHashFromArray($clone);

                    $searchHashes[$generatedHash] = true;

                    if (!\is_numeric($option['valueId'])) {
                        $clone[$fieldId] = \implode(\unpack("H*", $option['valueId']));
                        $generatedHash   = self::generateFieldHashFromArray($clone);

                        $searchHashes[$generatedHash] = true;
                    }
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }



        return \array_keys($searchHashes);
    }

    /**
     * @param $field
     * @return string
     */
    protected static function generateFieldHashFromArray($field)
    {
        $result = [];

        foreach ($field as $k => $ce) {
            $result[] = $k.':'.$ce;
        }

        return ';'.\implode(';', $result).';';
    }

    /**
     * Is the object a product field?
     *
     * @param mixed $object
     * @return boolean
     */
    public static function isField($object)
    {
        if (!\is_object($object)) {
            return false;
        }

        if ($object instanceof QUI\ERP\Products\Interfaces\FieldInterface) {
            return true;
        }

        return false;
    }

    /**
     * Validate the value of the field
     *
     * @param QUI\ERP\Products\Interfaces\FieldInterface $Field
     * @throws QUI\Exception
     */
    public static function validateField(QUI\ERP\Products\Interfaces\FieldInterface $Field)
    {
        $Field->validate($Field->getValue());
    }

    /**
     * Sort the fields by priority
     *
     * @param array $fields - FieldInterface[]
     * @param string $sort - sorting field
     * @return FieldInterface[]
     */
    public static function sortFields($fields, $sort = 'priority')
    {
        if (empty($fields)) {
            return $fields;
        }

        // allowed sorting
        switch ($sort) {
            case 'id':
            case 'title':
            case 'type':
            case 'name':
            case 'priority':
            case 'workingtitle':
                break;

            default:
                $sort = 'priority';
        }

        // if memory cache exists
        $cache = FieldSortCache::getFieldCache($fields, $sort);

        if ($cache) {
            return $cache;
        }

        // if no memory cache exists

        /**
         * @param QUI\ERP\Products\Field\Field $Field
         * @param string $field
         * @return mixed
         */
        $getFieldSortValue = function ($Field, $field) {
            if ($field === 'id') {
                return (int)$Field->getId();
            }

            if ($field === 'title') {
                return $Field->getTitle();
            }

            if ($field === 'type') {
                return $Field->getType();
            }

            if ($field === 'name') {
                return $Field->getName();
            }

            if ($field === 'workingtitle') {
                return $Field->getWorkingTitle();
            }

            return (int)$Field->getAttribute($field);
        };

        \usort($fields, function ($Field1, $Field2) use ($sort, $getFieldSortValue) {
            if (!self::isField($Field1)) {
                return 1;
            }

            if (!self::isField($Field2)) {
                return -1;
            }

            /* @var $Field1 QUI\ERP\Products\Field\Field */
            /* @var $Field2 QUI\ERP\Products\Field\Field */
            $priority1 = $getFieldSortValue($Field1, $sort);
            $priority2 = $getFieldSortValue($Field2, $sort);

            if (\is_string($priority1) || \is_string($priority2)) {
                return \strnatcmp($priority1, $priority2);
            }

            // if sorting is priority, and both are equal, than use title
            if ($sort === 'priority' && $priority1 === $priority2) {
                $priority1 = $getFieldSortValue($Field1, 'title');
                $priority2 = $getFieldSortValue($Field2, 'title');

                return \strnatcmp($priority1, $priority2);
            }

            if ($priority1 === 0) {
                return 1;
            }

            if ($priority2 === 0) {
                return -1;
            }

            if ($priority1 < $priority2) {
                return -1;
            }

            if ($priority1 > $priority2) {
                return 1;
            }

            return 0;
        });

        // cache the sorting
        $cache = [];

        foreach ($fields as $Field) {
            $cache[] = $Field->getId();
        }

        FieldSortCache::setFieldCache($fields, $sort, $cache);

        return $fields;
    }

    /**
     * Can the field used as a detail field?
     * JavaScript equivalent package/quiqqer/products/bin/utils/Fields
     *
     * @param mixed $Field
     * @return bool
     */
    public static function canUsedAsDetailField($Field)
    {
        /* @var $Field QUI\ERP\Products\Field\Field */
        if (!self::isField($Field)) {
            return false;
        }

        if ($Field->getId() == FieldHandler::FIELD_TITLE
            || $Field->getId() == FieldHandler::FIELD_CONTENT
            || $Field->getId() == FieldHandler::FIELD_SHORT_DESC
            || $Field->getId() == FieldHandler::FIELD_PRICE
            || $Field->getId() == FieldHandler::FIELD_IMAGE
        ) {
            return false;
        }

        if ($Field->getType() == FieldHandler::TYPE_ATTRIBUTE_LIST
            || $Field->getType() == FieldHandler::TYPE_FOLDER
            || $Field->getType() == FieldHandler::TYPE_TEXTAREA
            || $Field->getType() == FieldHandler::TYPE_TEXTAREA_MULTI_LANG
        ) {
            return false;
        }

        return true;
    }

    /**
     * Show the field in the details?
     *
     * @param mixed $Field
     * @return bool
     */
    public static function showFieldInProductDetails($Field)
    {
        /* @var $Field QUI\ERP\Products\Field\Field */
        if (!self::canUsedAsDetailField($Field)) {
            return false;
        }

        return $Field->showInDetails();
    }

    /**
     * Returns the value from a Weight Field in Kilogram
     *
     * @param $Field
     * @return int
     */
    public static function weightFieldToKilogram(QUI\ERP\Products\Field\Field $Field)
    {
        if ($Field->getId() !== QUI\ERP\Products\Handler\Fields::FIELD_WEIGHT) {
            return 0;
        }

        $value = $Field->getValue();

        if (empty($value)) {
            return 0;
        }

        return self::weightToKilogram($value['quantity'], $value['id']);
    }

    /**
     * Parses a weight value to kilogram
     *
     * @param float|string|int $value
     * @param string $unit - kg, g, t, tons, lbs, lb
     * @return float
     */
    public static function weightToKilogram($value, $unit)
    {
        $value = \floatval($value);

        if ($unit === 'kg') {
            return $value;
        }

        if (empty($unit)) {
            return $value;
        }

        switch ($unit) {
            case 'g':
                return $value / 1000;

            case 't':
            case 'tons':
                return $value * 1000;

            case 'lb':
            case 'lbs':
                return $value / 2.2046;
        }

        return $value;
    }

    /**
     * is the value a weight specification
     *
     * @param $weight
     * @return bool
     */
    public static function isWeight($weight)
    {
        switch ($weight) {
            case 'g':
            case 'kg':
            case 't':
            case 'tons':
            case 'lb':
            case 'lbs':
                return true;
        }

        return false;
    }

    /**
     * compares to numbers
     *
     * @param $no1
     * @param $no2
     * @param $type
     *
     * @return bool
     */
    public static function compare($no1, $no2, $type)
    {
        if ($type === '=') {
            return $no1 == $no2;
        }

        if ($type === 'gt') {
            return $no1 > $no1;
        }

        if ($type === 'egt') {
            return $no1 >= $no1;
        }

        if ($type === 'lt') {
            return $no1 < $no1;
        }

        if ($type === 'elt') {
            return $no1 <= $no1;
        }

        return false;
    }
}
