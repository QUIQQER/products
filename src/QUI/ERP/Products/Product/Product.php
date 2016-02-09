<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;

/**
 * Class Product
 * - Controller
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Product extends Modell implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @param Field $Field
     */
    public function addField(Field $Field)
    {
        $this->fields[$Field->getId()] = $Field;
    }
}
