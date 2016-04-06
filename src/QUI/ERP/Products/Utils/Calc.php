<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */
namespace QUI\ERP\Products\Utils;

use QUI\ERP\Products\Product\UniqueProduct;
use QUI\Interfaces\Users\User;
use QUI\ERP\Products\Handler\Fields;

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
     * @param UniqueProduct $Product
     * @return double|float|integer
     *
     * @todo muss richtig implementiert werden
     */
    public static function getProductPrice(UniqueProduct $Product)
    {
        $price  = $Product->getFieldValue(Fields::FIELD_PRICE);
        $prices = $Product->getPriceFactors();

        // methode vom grundpreis berechnen


        // @todo muss richtig implementiert werden
        if (method_exists($Product, 'getQuantity')) {
            $quantity = $Product->getQuantity();
            return $price * $quantity;
        }


        return new \QUI\ERP\Products\Utils\Price(
            $price,
            \QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }
}
