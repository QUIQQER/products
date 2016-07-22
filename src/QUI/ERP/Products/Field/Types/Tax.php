<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Tax
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class Tax extends Vat
{
    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Tax';
    }
}
