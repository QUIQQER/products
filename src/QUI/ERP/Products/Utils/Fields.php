<?php

/**
 * This file contains QUI\ERP\Products\Utils\Fields
 */
namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class Fields
 * @package QUI\ERP\Products
 */
class Fields
{
    /**
     * @param array $fields
     * @return array
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
                self::checkField($Field);

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
     * @param QUI\ERP\Products\Interfaces\Field $Field
     * @throws QUI\Exception
     */
    public static function checkField(QUI\ERP\Products\Interfaces\Field $Field)
    {
        $Field->checkValue(
            $Field->getValue()
        );
    }
}
