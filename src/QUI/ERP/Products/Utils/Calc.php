<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */
namespace QUI\ERP\Products\Utils;

use QUI\ERP\Products\Interfaces\Product;
use QUI\Interfaces\Users\User;

/**
 * Class Calc
 *
 * @package QUI\ERP\Products\Utils
 * @author www.pcsg.de (Henning Leutz)
 */
class Calc
{
    /**
     * @var User
     */
    protected static $User;

    /**
     * Set the calculation user
     * All calculations are made in dependence from this user
     *
     * @param User $User
     */
    public static function setUser(User $User)
    {
        self::$User = $User;
    }

    /**
     * Calculate the product price
     *
     * @param Product $Product
     * @return double|float|integer
     *
     * @todo muss richtig implementiert werden
     */
    public static function getProductPrice(Product $Product)
    {
        $Price  = $Product->getPrice();
        $fields = $Product->getFields();

        // @todo muss richtig implementiert werden
        if (method_exists($Product, 'getQuantity')) {
            $quantity = $Product->getQuantity();
            return $Price->getNetto() * $quantity;
        }


        return $Price->getNetto();
    }
}
