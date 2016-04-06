<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Textarea
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class Textarea
 * @package QUI\ERP\Products\Field
 */
class Textarea extends QUI\ERP\Products\Field\Field
{
    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Textarea';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        // TODO: Implement validate() method.
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return string
     */
    public function cleanup($value)
    {
        return $value;
    }
}
