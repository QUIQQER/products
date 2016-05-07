<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Utils\Price;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Currency\Handler as Currencies;

/**
 * Class Calc
 *
 * @package QUI\ERP\Products\Utils
 * @author www.pcsg.de (Henning Leutz)
 */
class Calc
{
    /**
     * Percentage calculation
     */
    const CALCULATION_PERCENTAGE = 1;

    /**
     * Standard calculation
     */
    const CALCULATION_COMPLEMENT = 2;


    /**
     * Basis calculation -> netto
     */
    const CALCULATION_BASIS_NETTO = 1;

    /**
     * Basis calculation -> from current price
     */
    const CALCULATION_BASIS_CURRENTPRICE = 2;


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
     * Calculate a complete product list
     *
     * @param ProductList $List
     * @return ProductList
     */
    public static function calcProductList(ProductList $List)
    {
        $products = $List->getProducts();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            self::getProductPrice($Product);
        }

        return $List;
    }

    /**
     * Calculate the product price
     *
     * @param UniqueProduct $Product
     * @return Price
     *
     * @todo muss richtig implementiert werden
     */
    public static function getProductPrice(UniqueProduct $Product)
    {
        $price  = $Product->getPrice()->getPrice();
        $prices = $Product->getPriceFactors()->sort();

        $basisPrice = $price;

        /* @var PriceFactor $PriceFactor */
        foreach ($prices as $PriceFactor) {
            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case Calc::CALCULATION_COMPLEMENT:
                    $price = $price + $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case Calc::CALCULATION_PERCENTAGE:
                    $percentage = 0;

                    switch ($PriceFactor->getCalculationBasis()) {
                        case Calc::CALCULATION_BASIS_NETTO:
                            $percentage = $PriceFactor->getValue() / 100 * $basisPrice;
                            break;

                        case Calc::CALCULATION_BASIS_CURRENTPRICE:
                            $percentage = $PriceFactor->getValue() / 100 * $price;
                            break;
                    }

                    $price = $price + $percentage;
                    break;
            }
        }


        // @todo muss richtig implementiert werden
        if (method_exists($Product, 'getQuantity')) {
            $quantity = $Product->getQuantity();
            return $price * $quantity;
        }


        return new Price($price, Currencies::getDefaultCurrency());
    }

}
