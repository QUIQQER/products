<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Products\Product\ProductList;

use QUI\ERP\Products\Utils\User as ProductUserUtils;
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
     * Basis brutto
     * include all price factors (from netto calculated price)
     * warning: its not brutto VAT
     *
     * geht vnn der netto basis aus, welche alle price faktoren schon beinhaltet
     * alle felder sind in diesem price schon enthalten
     */
    const CALCULATION_BASIS_BRUTTO = 3;

    /**
     * @var UserInterface
     */
    protected $User;

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
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        return new self($User);
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

            foreach ($productAttributes['calculated_vatArray'] as $vatEntry) {
                $vat = $vatEntry['vat'];

                if (!isset($vatArray[$vat])) {
                    $vatArray[$vat]        = $vatEntry;
                    $vatArray[$vat]['sum'] = 0;
                }

                $vatArray[$vat]['sum'] = $vatArray[$vat]['sum'] + $vatEntry['sum'];
            }
        }

        try {
            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcList',
                array($this, $List)
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
                case Calc::CALCULATION_COMPLEMENT:
                    $nettoSum       = $nettoSum + $PriceFactor->getValue();
                    $priceFactorSum = $priceFactorSum + $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case Calc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case Calc::CALCULATION_BASIS_NETTO:
                            $percentage = $PriceFactor->getValue() / 100 * $nettoSubSum;
                            break;

                        case Calc::CALCULATION_BASIS_BRUTTO:
                        case Calc::CALCULATION_BASIS_CURRENTPRICE:
                            $percentage = $PriceFactor->getValue() / 100 * $nettoSum;
                            break;
                    }

                    $nettoSum       = $this->round($nettoSum + $percentage);
                    $priceFactorSum = $priceFactorSum + $percentage;
                    break;
            }
        }

        // vat text
        $vatLists = array();
        $vatText  = array();
        $sum      = $nettoSum;

        foreach ($vatArray as $vatEntry) {
            $vat = $vatEntry['vat'];

            $vatLists[$vat] = true; // liste für MWST texte

            // rabatt abzug - rabatte müssen bei der MWST beachtet werden
            $vatSumPriceFactorSum = $this->round($priceFactorSum * ($vat / 100));

            $sum = $sum + $vatEntry['sum'] + $vatSumPriceFactorSum;

            // neu berechnung (aktualisierung) auf den summen eintrag in den MWST
            $vatArray[$vat]['sum'] = $vatArray[$vat]['sum'] + $vatSumPriceFactorSum;
        }

        foreach ($vatLists as $vat => $bool) {
            $vatText[$vat] = $this->getVatText($vat, $this->getUser());
        }

        $callback(array(
            'sum'          => $sum,
            'subSum'       => $subSum,
            'nettoSum'     => $nettoSum,
            'nettoSubSum'  => $nettoSubSum,
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
     */
    public function getProductPrice(UniqueProduct $Product, $callback = false)
    {
        // calc data
        if (!is_callable($callback)) {
            $Product->calc($this);

            return $Product->getPrice();
        }

        $isNetto     = QUI\ERP\Products\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area        = QUI\ERP\Products\Utils\User::getUserArea($this->getUser());

        $nettoPrice   = $this->findProductPriceField($Product)->getNetto();
        $priceFactors = $Product->getPriceFactors()->sort();

        $factors                    = array();
        $basisNettoPrice            = $nettoPrice;
        $calculationBasisBruttoList = array();

        /* @var PriceFactor $PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            if ($PriceFactor->getCalculationBasis() == Calc::CALCULATION_BASIS_BRUTTO) {
                $calculationBasisBruttoList[] = $PriceFactor;
                continue;
            }

            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                default:
                case Calc::CALCULATION_COMPLEMENT:
                    $priceFactorSum = $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case Calc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case Calc::CALCULATION_BASIS_NETTO:
                            $priceFactorSum = $PriceFactor->getValue() / 100 * $basisNettoPrice;
                            break;

                        case Calc::CALCULATION_BASIS_CURRENTPRICE:
                            $priceFactorSum = $PriceFactor->getValue() / 100 * $nettoPrice;
                            break;
                    }
            }

            $nettoPrice       = $nettoPrice + $priceFactorSum;
            $priceFactorArray = $PriceFactor->toArray();

            $priceFactorArray['sum'] = $priceFactorSum;

            $factors[] = $priceFactorArray;
        }

        // Calc::CALCULATION_BASIS_BRUTTO
        foreach ($calculationBasisBruttoList as $PriceFactor) {
            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case Calc::CALCULATION_COMPLEMENT:
                    $nettoPrice = $nettoPrice + $PriceFactor->getValue();
                    break;

                // Prozent Angabe
                case Calc::CALCULATION_PERCENTAGE:
                    $percentage = $PriceFactor->getValue() / 100 * $nettoPrice;
                    $nettoPrice = $nettoPrice + $percentage;
                    break;
            }
        }

        // mwst
        $Tax    = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
        $vatSum = $nettoPrice * ($Tax->getValue() / 100);

        $bruttoPrice = $this->round($nettoPrice + $vatSum);

        // sum
        $nettoSum  = $this->round($nettoPrice * $Product->getQuantity());
        $vatSum    = $nettoSum * ($Tax->getValue() / 100);
        $bruttoSum = $this->round($nettoSum + $vatSum);

        $price = $isNetto ? $nettoPrice : $bruttoPrice;
        $sum   = $isNetto ? $nettoSum : $bruttoSum;


        // vat array
        $Taxes     = new QUI\ERP\Tax\Handler();
        $vatFields = $Product->getFieldsByType(FieldHandler::TYPE_VAT);
        $vatArray  = array();
        $vatText   = array();

        /* @var $Vat QUI\ERP\Products\Field\UniqueField */
        foreach ($vatFields as $Vat) {
            if ($Vat->getValue() === false) {
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
                'sum'  => $this->round($nettoSum * ($TaxEntry->getValue() / 100)),
                'text' => $this->getVatText($TaxEntry->getValue(), $this->getUser())
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
            'currencyData' => '',
            'factors'      => $factors
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
        $priceList = $Product->getFieldsByType('Price');

        if (empty($priceList)) {
            return new QUI\ERP\Products\Utils\Price($PriceField->getValue(), $Currency);
        }

        // @todo speizial preisfelder beachten, zb EK Preis

        $priceFields = array_filter($priceList, function ($Field) use ($User) {
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
    public function round($value)
    {
        $decimalSeperator  = $this->getUser()->getLocale()->getDecimalSeperator();
        $groupingSeperator = $this->getUser()->getLocale()->getGroupingSeperator();
        $precision         = 8; // nachkommstelle beim rundne -> @todo in die conf?

        if (strpos($value, $decimalSeperator) && $decimalSeperator != ' . ') {
            $value = str_replace($groupingSeperator, '', $value);
        }

        $value = str_replace(',', ' . ', $value);
        $value = round($value, $precision);

        return $value;
    }


    /**
     * text
     */

    /**
     * Return the tax message for an user
     *
     * @param UserInterface $User
     * @return string
     */
    protected function getVatTextByUser(UserInterface $User)
    {
        $Tax = QUI\ERP\Tax\Utils::getTaxByUser($User);
        $vat = $Tax->getValue() . ' % ';

        return $this->getVatText($vat, $User);
    }

    /**
     * Return tax text
     * eq: incl or zzgl
     *
     * @param integer $vat
     * @param UserInterface $User
     * @return array|string
     */
    protected function getVatText($vat, UserInterface $User)
    {
        $Locale = $User->getLocale();

        if (ProductUserUtils::isNettoUser($User)) {
            if (QUI\ERP\Tax\Utils::isUserEuVatUser($User)) {
                return $Locale->get(
                    'quiqqer/tax',
                    'message.vat.text.netto.EUVAT',
                    array('vat' => $vat)
                );
            }

            return $Locale->get(
                'quiqqer/tax',
                'message.vat.text.netto',
                array('vat' => $vat)
            );
        }

        if (QUI\ERP\Tax\Utils::isUserEuVatUser($User)) {
            return $Locale->get(
                'quiqqer/tax',
                'message.vat.text.brutto.EUVAT',
                array('vat' => $vat)
            );
        }

        return $Locale->get(
            'quiqqer/tax',
            'message.vat.text.brutto',
            array('vat' => $vat)
        );
    }
}
