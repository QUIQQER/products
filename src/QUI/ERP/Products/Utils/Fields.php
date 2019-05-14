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
            || $Field->getType() == FieldHandler::TYPE_PRODCUCTS
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
}
