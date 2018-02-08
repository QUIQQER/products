<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Tax\Utils as TaxUtils;
use QUI\ERP\Accounting\Calc as ErpCalc;

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
     *
     * @deprecated use QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE
     */
    const CALCULATION_PERCENTAGE = ErpCalc::CALCULATION_PERCENTAGE;

    /**
     * Standard calculation
     *
     * @deprecated use QUI\ERP\Accounting\Calc::
     */
    const CALCULATION_COMPLEMENT = ErpCalc::CALCULATION_COMPLEMENT;

    /**
     * Basis calculation -> netto
     *
     * @deprecated use QUI\ERP\Accounting\Calc::
     */
    const CALCULATION_BASIS_NETTO = ErpCalc::CALCULATION_BASIS_NETTO;

    /**
     * Basis calculation -> from current price
     *
     * @deprecated use QUI\ERP\Accounting\Calc::
     */
    const CALCULATION_BASIS_CURRENTPRICE = ErpCalc::CALCULATION_BASIS_CURRENTPRICE;

    /**
     * Basis brutto
     * include all price factors (from netto calculated price)
     * warning: its not brutto VAT
     *
     * geht vnn der netto basis aus, welche alle price faktoren schon beinhaltet
     * alle felder sind in diesem price schon enthalten
     *
     * @deprecated use QUI\ERP\Accounting\Calc::
     */
    const CALCULATION_BASIS_BRUTTO = ErpCalc::CALCULATION_BASIS_BRUTTO;

    /**
     * @var UserInterface
     */
    protected $User = null;

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected $Currency = null;

    /**
     * Flag for ignore vat calculation (force ignore VAT)
     *
     * @var bool
     */
    protected $ignoreVatCalculation = false;

    /**
     * Calc constructor.
     *
     * @param UserInterface|bool $User - calculation user
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
     * @param UserInterface|bool $User - optional
     * @return Calc
     */
    public static function getInstance($User = false)
    {
        if (!$User && QUI::isBackend()) {
            $User = QUI::getUsers()->getSystemUser();
        }

        if (!QUI::getUsers()->isUser($User)
            && !QUI::getUsers()->isSystemUser($User)
        ) {
            $User = QUI::getUserBySession();
        }

        $Calc = new self($User);

        if (QUI::getUsers()->isSystemUser($User) && QUI::isBackend()) {
            $Calc->ignoreVatCalculation();
        }

        return $Calc;
    }

    /**
     * Static instance create
     */
    public function ignoreVatCalculation()
    {
        $this->ignoreVatCalculation = true;
    }

    /**
     * Set the calculation user
     * All calculations are made in dependence from this user
     *
     * @param UserInterface $User
     */
    public function setUser(UserInterface $User)
    {
        $this->User = $User;
    }

    /**
     * Return the calc user
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Return the currency
     *
     * @return QUI\ERP\Currency\Currency
     */
    public function getCurrency()
    {
        if (is_null($this->Currency)) {
            $this->Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        }

        return $this->Currency;
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
        $isNetto     = QUI\ERP\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area        = QUI\ERP\Utils\User::getUserArea($this->getUser());

        if ($this->ignoreVatCalculation) {
            $isNetto = true;
        }

        $subSum   = 0;
        $nettoSum = 0;
        $vatArray = array();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            // add netto price
            try {
                QUI::getEvents()->fireEvent(
                    'onQuiqqerProductsCalcListProduct',
                    array($this, $Product)
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::write($Exception->getMessage(), QUI\System\Log::LEVEL_ERROR);
            }

            $this->getProductPrice($Product);

            $productAttributes = $Product->getAttributes();

            $subSum   = $subSum + $productAttributes['calculated_sum'];
            $nettoSum = $nettoSum + $productAttributes['calculated_nettoSum'];

            $productVatArray = $productAttributes['calculated_vatArray'];
            $vat             = $productVatArray['vat'];

            if (!isset($vatArray[$vat])) {
                $vatArray[$vat]        = $productVatArray;
                $vatArray[$vat]['sum'] = 0;
            }

            $vatArray[$vat]['sum'] = $vatArray[$vat]['sum'] + $productVatArray['sum'];
        }

        QUI\ERP\Debug::getInstance()->log('Berechnetet Produktliste MwSt', 'quiqqer/product');
        QUI\ERP\Debug::getInstance()->log($vatArray, 'quiqqer/product');

        try {
            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcList',
                array($this, $List, $nettoSum)
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::write($Exception->getMessage(), QUI\System\Log::LEVEL_ERROR);
        }

        // price factors
        $priceFactors   = $List->getPriceFactors()->sort();
        $nettoSubSum    = $nettoSum;
        $priceFactorSum = 0;

        /* @var $PriceFactor PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case ErpCalc::CALCULATION_COMPLEMENT:
                    $nettoSum       = $nettoSum + $PriceFactor->getValue();
                    $priceFactorSum = $priceFactorSum + $PriceFactor->getValue();

                    $PriceFactor->setNettoSum($this, $PriceFactor->getValue());
                    break;

                // Prozent Angabe
                case ErpCalc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case ErpCalc::CALCULATION_BASIS_NETTO:
                            $percentage = $PriceFactor->getValue() / 100 * $nettoSubSum;
                            break;

                        case ErpCalc::CALCULATION_BASIS_BRUTTO:
                        case ErpCalc::CALCULATION_BASIS_CURRENTPRICE:
                            $percentage = $PriceFactor->getValue() / 100 * $nettoSum;
                            break;
                    }

                    $PriceFactor->setNettoSum($this, $percentage);

                    $nettoSum       = $this->round($nettoSum + $percentage);
                    $priceFactorSum = $priceFactorSum + $percentage;
                    break;

                default:
                    continue;
            }


            // add pricefactor VAT
            if (!($PriceFactor instanceof QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface)) {
                continue;
            }

            /* @var $PriceFactor QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface */
            $VatType = $PriceFactor->getVatType();
            $Vat     = QUI\ERP\Tax\Utils::getTaxEntry($VatType, $Area);
            $vatSum  = $PriceFactor->getNettoSum() * ($Vat->getValue() / 100);
            $vat     = $Vat->getValue();

            $PriceFactor->setBruttoSum($this, $vatSum + $PriceFactor->getNettoSum());

            if (!isset($vatArray[$vat])) {
                $vatArray[$vat] = array(
                    'vat'  => $vat,
                    'text' => ErpCalc::getVatText($Vat->getValue(), $this->getUser())
                );

                $vatArray[$vat]['sum'] = 0;
            }

            $vatArray[$vat]['sum'] = $vatArray[$vat]['sum'] + $vatSum;
        }

        // vat text
        $vatLists  = array();
        $vatText   = array();
        $bruttoSum = $nettoSum;

        foreach ($vatArray as $vatEntry) {
            $vatLists[$vatEntry['vat']] = true; // liste für MWST texte

            $bruttoSum = $bruttoSum + $vatEntry['sum'];
        }

        foreach ($vatLists as $vat => $bool) {
            $vatText[$vat] = ErpCalc::getVatText($vat, $this->getUser());
        }

        if ($this->ignoreVatCalculation) {
            $vatArray = array();
            $vatText  = array();
        }

        $callback(array(
            'sum'          => $bruttoSum,
            'subSum'       => $subSum,
            'nettoSum'     => $nettoSum,
            'nettoSubSum'  => $nettoSubSum,
            'vatArray'     => $vatArray,
            'vatText'      => $vatText,
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => $this->getCurrency()->toArray()
        ));

        return $List;
    }

    /**
     * Calculate the product price
     * only fields
     *
     * @param UniqueProduct $Product
     * @param callable|boolean $callback - optional, callback function for the calculated data array
     * @return QUI\ERP\Money\Price
     */
    public function getProductPrice(UniqueProduct $Product, $callback = false)
    {
        // calc data
        if (!is_callable($callback)) {
            $Product->calc($this);

            return $Product->getPrice();
        }

        $isNetto     = QUI\ERP\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area        = QUI\ERP\Utils\User::getUserArea($this->getUser());

        $nettoPrice   = $Product->getNettoPrice()->getNetto();
        $priceFactors = $Product->getPriceFactors()->sort();

        $factors                    = array();
        $basisNettoPrice            = $nettoPrice;
        $calculationBasisBruttoList = array();

        /* @var PriceFactor $PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            if ($PriceFactor->getCalculationBasis() == ErpCalc::CALCULATION_BASIS_BRUTTO) {
                $calculationBasisBruttoList[] = $PriceFactor;
                continue;
            }

            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                default:
                case ErpCalc::CALCULATION_COMPLEMENT:
                    $priceFactorSum = $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case ErpCalc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case ErpCalc::CALCULATION_BASIS_NETTO:
                            $priceFactorSum = $PriceFactor->getValue() / 100 * $basisNettoPrice;
                            break;

                        case ErpCalc::CALCULATION_BASIS_CURRENTPRICE:
                            $priceFactorSum = $PriceFactor->getValue() / 100 * $nettoPrice;
                            break;
                    }
            }

            $PriceFactor->setNettoSum($this, $priceFactorSum * $Product->getQuantity());

            $nettoPrice       = $nettoPrice + $priceFactorSum;
            $priceFactorArray = $PriceFactor->toArray();

            $priceFactorArray['sum'] = $priceFactorSum;

            $factors[] = $priceFactorArray;
        }

        // Calc::CALCULATION_BASIS_BRUTTO
        foreach ($calculationBasisBruttoList as $PriceFactor) {
            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case ErpCalc::CALCULATION_COMPLEMENT:
                    $nettoPrice = $nettoPrice + $PriceFactor->getValue();
                    $PriceFactor->setNettoSum($this, $PriceFactor->getValue());
                    break;

                // Prozent Angabe
                case ErpCalc::CALCULATION_PERCENTAGE:
                    $percentage = $PriceFactor->getValue() / 100 * $nettoPrice;
                    $nettoPrice = $nettoPrice + $percentage;
                    $PriceFactor->setNettoSum($this, $percentage);
                    break;
            }
        }


        // TAX Fields
        $taxFields = $Product->getFieldsByType(FieldHandler::TYPE_TAX);

        /* @var $Tax QUI\ERP\Products\Field\UniqueField */
        foreach ($taxFields as $Tax) {
            if ($Tax->getValue() === false) {
                continue;
            }

            try {
                $TaxType  = new QUI\ERP\Tax\TaxType($Tax->getValue());
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            } catch (QUI\Exception $Exception) {
                QUI\ERP\Debug::getInstance()->log($Exception, 'quiqqer/products');
                continue;
            }

            // steuern auf netto preis addieren
            $taxNettoPrice = $this->round($nettoPrice * ($TaxEntry->getValue() / 100));
            $nettoPrice    = $nettoPrice + $taxNettoPrice;
        }


        // MwSt / VAT
        $Vat = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());

        // Wenn Produkt eigene VAT gesetzt hat und diese zum Benutzer passt
        $ProductVat = $Product->getField(Fields::FIELD_VAT);

        try {
            $TaxType  = new QUI\ERP\Tax\TaxType($ProductVat->getValue());
            $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            $Vat      = $TaxEntry;
        } catch (QUI\Exception $Exception) {
            QUI\ERP\Debug::getInstance()->log(
                'Produt Vat ist nicht für den Benutzer gültig',
                'quiqqer/products'
            );
        }

        $vatSum      = $nettoPrice * ($Vat->getValue() / 100);
        $bruttoPrice = $this->round($nettoPrice + $vatSum);

        // sum
        $nettoSum  = $this->round($nettoPrice * $Product->getQuantity());
        $vatSum    = $nettoSum * ($Vat->getValue() / 100);
        $bruttoSum = $this->round($nettoSum + $vatSum);

        $price      = $isNetto ? $nettoPrice : $bruttoPrice;
        $sum        = $isNetto ? $nettoSum : $bruttoSum;
        $basisPrice = $isNetto ? $basisNettoPrice : $basisNettoPrice + ($basisNettoPrice * $Vat->getValue() / 100);

        $vatArray = array(
            'vat'  => $Vat->getValue(),
            'sum'  => $this->round($nettoSum * ($Vat->getValue() / 100)),
            'text' => ErpCalc::getVatText($Vat->getValue(), $this->getUser())
        );


        QUI\ERP\Debug::getInstance()->log(
            'Kalkulierter Produkt Preis '.$Product->getId(),
            'quiqqer/products'
        );

        QUI\ERP\Debug::getInstance()->log(array(
            'basisPrice'   => $basisPrice,
            'price'        => $price,
            'sum'          => $sum,
            'nettoSum'     => $nettoSum,
            'nettoPrice'   => $nettoPrice,
            'vatArray'     => $vatArray,
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => $this->getCurrency()->toArray(),
            'factors'      => $factors
        ), 'quiqqer/products');


        $callback(array(
            'basisPrice'   => $basisPrice,
            'price'        => $price,
            'sum'          => $sum,
            'nettoSum'     => $nettoSum,
            'nettoPrice'   => $nettoPrice,
            'vatArray'     => $vatArray,
            'vatText'      => $vatArray['text'],
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => $this->getCurrency()->toArray(),
            'factors'      => $factors
        ));

        return $Product->getPrice();
    }

    /**
     * Rounds the value via shop config
     *
     * @param string $value
     * @return float|mixed
     */
    public function round($value)
    {
        return QUI\ERP\Accounting\Calc::getInstance($this->getUser())->round($value);
    }

    /**
     * Calc the price in dependence of the user
     *
     * @param int|double|float $nettoPrice - netto price
     * @return int|double|float
     */
    public function getPrice($nettoPrice)
    {
        $isNetto = QUI\ERP\Utils\User::isNettoUser($this->getUser());

        if ($isNetto) {
            return $nettoPrice;
        }

        $Tax    = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
        $vatSum = $nettoPrice * ($Tax->getValue() / 100);

        return $this->round($nettoPrice + $vatSum);
    }

    /**
     * text
     */

    /**
     * Return the tax message for an user
     *
     * @return string
     */
    public function getVatTextByUser()
    {
        return ErpCalc::getVatText(
            QUI\ERP\Tax\Utils::getTaxByUser($this->getUser())->getValue(),
            $this->getUser()
        );
    }
}
