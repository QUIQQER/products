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
    protected $User;

    /**
     * Calc constructor.
     *
     * @param User|bool $User - calculation user
     */
    public function __construct($User = false)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        $this->User = $User;
    }

    /**
     * Static instance create
     *
     * @param User|bool $User - optional
     * @return Calc
     */
    public static function getInstance($User = false)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        return new self($User);
    }

    /**
     * Set the calculation user
     * All calculations are made in dependence from this user
     *
     * @param User $User
     */
    public function setUser(User $User)
    {
        $this->User = $User;
    }

    /**
     * Return the calc user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Calculate a complete product list
     *
     * @param ProductList $List
     * @param callable|boolean $callback - optional, callback function for the data array
     * @return ProductList
     */
    public function calcProductList(ProductList $List, $callback = false)
    {
        // calc data
        if (!is_callable($callback)) {
            return $List->calc();
        }

        $products = $List->getProducts();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            // add netto price

            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcListProduct',
                array($this, $Product)
            );

            $this->getProductPrice($Product);
        }

        QUI::getEvents()->fireEvent(
            'onQuiqqerProductsCalcList',
            array($this, $List)
        );


        $callback(array(
            'sum'             => '',
            'subSum'          => '',
            'nettoSum'        => '',
            'displaySum'      => '',
            'displaySubSum'   => '',
            'displayNettoSum' => '',
            'vatArray'        => '',
            'vatText'         => '',
            'isEuVat'         => QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser()),
            'isNetto'         => QUI\ERP\Products\Utils\User::isNettoUser($this->getUser()),
            'currencyData'    => ''
        ));

        return $List;
    }

    /**
     * Calculate the product price
     * only fields
     *
     * @param UniqueProduct $Product
     * @param callable|boolean $callback - optional, callback function for the calculated data array
     * @return Price
     *
     * @todo muss richtig implementiert werden
     */
    public function getProductPrice(UniqueProduct $Product, $callback = false)
    {
        // calc data
        if (!is_callable($callback)) {
            $Product->calc();

            return $Product->getPrice();
        }

        $isNetto      = QUI\ERP\Products\Utils\User::isNettoUser($this->getUser());
        $nettoPrice   = self::findProductPriceField($Product)->getNetto();
        $priceFactors = $Product->getPriceFactors()->sort();

        $basisNettoPrice = $nettoPrice;

        /* @var PriceFactor $PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case Calc::CALCULATION_COMPLEMENT:
                    $nettoPrice = $nettoPrice + $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case Calc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case Calc::CALCULATION_BASIS_NETTO:
                            $percentage = $PriceFactor->getValue() / 100 * $basisNettoPrice;
                            break;

                        case Calc::CALCULATION_BASIS_CURRENTPRICE:
                            $percentage = $PriceFactor->getValue() / 100 * $nettoPrice;
                            break;
                    }

                    $nettoPrice = $nettoPrice + $percentage;
                    break;
            }
        }

        // mwst
        $Tax         = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
        $bruttoPrice = self::round($nettoPrice * $Tax->getValue());

        // sum
        $nettoSum  = self::round($nettoPrice * $Product->getQuantity());
        $bruttoSum = self::round($nettoSum * $Tax->getValue());

        $price = $isNetto ? $nettoPrice : $bruttoPrice;
        $sum   = $isNetto ? $nettoSum : $bruttoSum;

        $callback(array(
            'price'           => $price,
            'sum'             => $sum,
            'nettoSum'        => $nettoSum,
            'displaySum'      => '',
            'displayNettoSum' => '',
            'vatArray'        => '',
            'vatText'         => '',
            'isEuVat'         => QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser()),
            'isNetto'         => QUI\ERP\Products\Utils\User::isNettoUser($this->getUser()),
            'currencyData'    => ''
        ));

        return $Product->getPrice();
    }

    /**
     * Find the the product price field
     * If the product have multiple price fields
     *
     * @param UniqueProduct $Product
     * @return \QUI\ERP\Products\Utils\Price
     */
    protected function findProductPriceField(UniqueProduct $Product)
    {
        $Currency   = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $PriceField = $Product->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRICE);
        $User       = $this->getUser();

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

    /**
     * Rounds the value via shop config
     *
     * @param string $value
     * @return float|mixed
     */
    public static function round($value)
    {
        $decimalSeperator  = QUI::getLocale()->getDecimalSeperator();
        $groupingSeperator = QUI::getLocale()->getGroupingSeperator();
        $precision         = 8; // nachkommstelle beim rundne -> @todo in die conf?

        if (strpos($value, $decimalSeperator) && $decimalSeperator != '.') {
            $value = str_replace($groupingSeperator, '', $value);
        }

        $value = str_replace(',', '.', $value);
        $value = round($value, $precision);

        return $value;
    }
}
