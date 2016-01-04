<?php

/**
 * This file contains QUI\Products\Fields
 */
namespace QUI\Products;

use QUI;

/**
 * Class Fields
 * @package QUI\Products
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
            if (get_class($Field) != 'QUI\Products\Field') {
                continue;
            }

            try {
                self::checkField($Field);

                $result[] = $Field->toProductArray();

            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * @param Field $Field
     * @throws QUI\Exception
     */
    public static function checkField(Field $Field)
    {

    }
}
