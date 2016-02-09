<?php

/**
 * This file contains QUI\ERP\Products\Product\Controller
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;

/**
 * Class Controller
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Controller extends Product implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @param Field $Field
     */
    public function addField(Field $Field)
    {
        $this->fields[$Field->getId()] = $Field;
    }
}
