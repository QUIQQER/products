<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Tax\Utils as TaxUtils;

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

        $products    = $List->getProducts();
        $isNetto     = QUI\ERP\Products\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());

        $sum      = 0;
        $subSum   = 0;
        $nettoSum = 0;
        $vatArray = array();
        $vatText  = array();


        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            // add netto price

            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcListProduct',
                array($this, $Product)
            );

            $this->getProductPrice($Product);

            $productAttributes = $Product->getAttributes();

            $subSum   = $subSum + $productAttributes['calculated_price'];
            $sum      = $sum + $productAttributes['calculated_price'];
            $nettoSum = $nettoSum + $productAttributes['calculated_nettoSum'];
            $vatArray = array_merge($vatArray, $productAttributes['calculated_vatArray']);
        }

        QUI::getEvents()->fireEvent(
            'onQuiqqerProductsCalcList',
            array($this, $List)
        );

        // price factors
        $priceFactors    = $List->getPriceFactors()->sort();
        $basisNettoPrice = $nettoSum;

        /* @var $PriceFactor PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case Calc::CALCULATION_COMPLEMENT:
                    $nettoSum = $nettoSum + $PriceFactor->getValue();
                    $sum      = $sum + $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case Calc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case Calc::CALCULATION_BASIS_NETTO:
                            $percentage = $PriceFactor->getValue() / 100 * $basisNettoPrice;
                            break;

                        case Calc::CALCULATION_BASIS_CURRENTPRICE:
                            $percentage = $PriceFactor->getValue() / 100 * $nettoSum;
                            break;
                    }

                    $nettoSum = self::round($nettoSum + $percentage);
                    $sum      = self::round($sum + $percentage);
                    break;
            }
        }

        // vat text
        $vatLists = array();

        foreach ($vatArray as $vatEntry) {
            $vatLists[$vatEntry['vat']] = true;
        }
        var_dump($vatLists);

        $callback(array(
            'sum'          => $sum,
            'subSum'       => $subSum,
            'nettoSum'     => $nettoSum,
            'vatArray'     => $vatArray,
            'vatText'      => $vatText,
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => ''
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

        $isNetto     = QUI\ERP\Products\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area        = QUI\ERP\Products\Utils\User::getUserArea($this->getUser());

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
        $Tax    = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
        $vatSum = $nettoPrice * ($Tax->getValue() / 100);

        $bruttoPrice = self::round($nettoPrice + $vatSum);

        // sum
        $nettoSum  = self::round($nettoPrice * $Product->getQuantity());
        $vatSum    = $nettoSum * ($Tax->getValue() / 100);
        $bruttoSum = self::round($nettoSum + $vatSum);

        $price = $isNetto ? $nettoPrice : $bruttoPrice;
        $sum   = $isNetto ? $nettoSum : $bruttoSum;


        // vat array
        $Taxes     = new QUI\ERP\Tax\Handler();
        $vatFields = $Product->getFieldsByType(FieldHandler::TYPE_VAT);
        $vatArray  = array();
        $vatText   = array();

        /* @var $Vat QUI\ERP\Products\Field\UniqueField */
        foreach ($vatFields as $Vat) {
            if ($Vat->getValue() === false || $Vat->getValue() < 0) {
                continue;
            }

            try {
                $TaxType  = $Taxes->getTaxType($Vat->getValue());
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);

            } catch (QUI\Exception $Exception) {
                continue;
            }

            $vatArray[] = array(
                'vat'  => $TaxEntry->getValue(),
                'sum'  => self::round($nettoSum * ($TaxEntry->getValue() / 100)),
                'text' => $Vat->getTitle()
            );
        }

        $callback(array(
            'price'        => $price,
            'sum'          => $sum,
            'nettoSum'     => $nettoSum,
            'vatArray'     => $vatArray,
            'vatText'      => $vatText,
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => ''
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
