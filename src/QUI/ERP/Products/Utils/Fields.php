<?php

/**
 * This file contains QUI\ERP\Products\Utils\Fields
 */
namespace QUI\ERP\Products\Utils;

use QUI;

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
     *
     * @todo wer hat diese methode gebaut? ToJson = return string, wieso array?
     */
    public static function parseFieldsToJson($fields = array())
    {
        $result = array();

        foreach ($fields as $Field) {
            if (!self::isField($Field)) {
                continue;
            }

            /* @var $Field QUI\ERP\Products\Interfaces\Field */
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
        if (!is_object($object)) {
            return false;
        }

        if ($object instanceof QUI\ERP\Products\Interfaces\Field) {
            return true;
        }

        return false;
    }

    /**
     * Validate the value of the field
     *
     * @param QUI\ERP\Products\Interfaces\Field $Field
     * @throws QUI\Exception
     */
    public static function validateField(QUI\ERP\Products\Interfaces\Field $Field)
    {
        $Field->validate($Field->getValue());
    }

    /**
     * Sort the fields by priority
     *
     * @param array $fields
     * @return array
     */
    public static function sortFields($fields)
    {
        usort($fields, function ($Field1, $Field2) {
            if (!self::isField($Field1)) {
                return 1;
            }

            if (!self::isField($Field2)) {
                return -1;
            }

            /* @var $Field1 QUI\ERP\Products\Field\Field */
            /* @var $Field2 QUI\ERP\Products\Field\Field */
            $priority1 = (int)$Field1->getAttribute('priority');
            $priority2 = (int)$Field2->getAttribute('priority');

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
}
