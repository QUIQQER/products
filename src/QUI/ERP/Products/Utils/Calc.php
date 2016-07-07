<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\ERP\Products\Product\UniqueProduct;
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
            $PriceFactor = $Product->getPriceFactors();

            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcListProduct',
                array($PriceFactor, $Product)
            );

            self::getProductPrice($Product);
        }

        QUI::getEvents()->fireEvent(
            'onQuiqqerProductsCalcList',
            array($List)
        );


        // calc


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
        $price  = self::findProductPriceField($Product)->getNetto();
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
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
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

        // quantity
        $price = $price * $Product->getQuantity();

        return new Price($price, Currencies::getDefaultCurrency());
    }

    /**
     *
     *
     * @param UniqueProduct $Product
     * @return \QUI\ERP\Products\Utils\Price
     */
    protected static function findProductPriceField(UniqueProduct $Product)
    {
        $Currency   = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $PriceField = $Product->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRICE);

        // @todo product user???
        $User = QUI::getUserBySession();

        // exists more price fields?
        // is user in group filter
        $priceFields = array_filter($Product->getFieldsByType('Price'), function ($Field) use ($User) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */

            // ignore default main price
            if ($Field->getId() == QUI\ERP\Products\Handler\Fields::FIELD_PRICE) {
                return false;
            };

            $options = $Field->getOptions();

            if (!isset($options['groups'])) {
                return false;
            }

            $groups = explode(',', $options['groups']);

            if (empty($groups)) {
                return false;
            }

            foreach ($groups as $gid) {
                if ($User->isInGroup($gid)) {
                    return true;
                }
            }

            return false;
        });

        // use the lowest price?
        foreach ($priceFields as $Field) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */
            if ($Field->getValue() < $PriceField->getValue()) {
                $PriceField = $Field;
            }
        }

        return new QUI\ERP\Products\Utils\Price($PriceField->getValue(), $Currency);
    }
}
