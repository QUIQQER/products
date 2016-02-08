<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Url
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class Url extends QUI\ERP\Products\Field\Field
{
    public function getBackendView()
    {
        // TODO: Implement getBackendView() method.
    }

    public function getFrontendView()
    {
        // TODO: Implement getFrontendView() method.
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public static function validate($value)
    {
        // TODO: Implement validate() method.
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public static function cleanup($value)
    {
        // TODO: Implement cleanup() method.
    }
}
