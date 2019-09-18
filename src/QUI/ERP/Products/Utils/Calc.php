<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Interfaces\Users\User as UserInterface;

use QUI\ERP\Products\Handler\Fields;
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
     * @todo
     * Das deprecated sollte in ERP::CALCULATION_PERCENTAGE
     * Dazu müssten die Preisfaktoren vielleicht in ERP ren und aus Produkte
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

        if (!QUI::getUsers()->isUser($User) && !QUI::getUsers()->isSystemUser($User)) {
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
     * Set the currency for the calculation
     *
     * @param QUI\ERP\Currency\Currency $Currency
     */
    public function setCurrency(QUI\ERP\Currency\Currency $Currency)
    {
        $this->Currency = $Currency;
    }

    /**
     * Return the currency
     *
     * @return QUI\ERP\Currency\Currency
     */
    public function getCurrency()
    {
        if (\is_null($this->Currency)) {
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
     *
     * @throws QUI\Exception
     */
    public function calcProductList(ProductList $List, $callback = false)
    {
        // calc data
        if (!\is_callable($callback)) {
            return $List->calc();
        }

        $products    = $List->getProducts();
        $isNetto     = QUI\ERP\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area        = QUI\ERP\Utils\User::getUserArea($this->getUser());
        $Locale      = QUI\ERP\Products\Handler\Products::getLocale();

        if ($this->ignoreVatCalculation) {
            $isNetto = true;
        }

        $subSum   = 0;
        $nettoSum = 0;
        $vatArray = [];

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            // add netto price
            try {
                QUI::getEvents()->fireEvent(
                    'onQuiqqerProductsCalcListProduct',
                    [$this, $Product]
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::write($Exception->getMessage(), QUI\System\Log::LEVEL_ERROR);
            }

            $this->getProductPrice($Product);

            $productAttributes = $Product->getAttributes();

            $subSum   = $subSum + $productAttributes['calculated_sum'];
            $nettoSum = $nettoSum + $productAttributes['calculated_nettoSum'];

            $productVatArray = $productAttributes['calculated_vatArray'];

            if (!isset($productVatArray['vat'])) {
                continue;
            }

            $vat = $productVatArray['vat'];

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
                [$this, $List, $nettoSum]
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
            $priceFactorValue = $PriceFactor->getValue();

            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case ErpCalc::CALCULATION_COMPLEMENT:
                    // quiqqer/order#55
                    if ($nettoSum + $priceFactorValue <= 0) {
                        $priceFactorValue = $priceFactorValue - ($nettoSum + $priceFactorValue);
                    }

                    $nettoSum       = $nettoSum + $priceFactorValue;
                    $priceFactorSum = $priceFactorSum + $priceFactorValue;

                    $PriceFactor->setNettoSum($priceFactorValue);
                    break;

                // Prozent Angabe
                case ErpCalc::CALCULATION_PERCENTAGE:
                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case ErpCalc::CALCULATION_BASIS_NETTO:
                            $percentage = $priceFactorValue / 100 * $nettoSubSum;
                            break;

                        case ErpCalc::CALCULATION_BASIS_BRUTTO:
                        case ErpCalc::CALCULATION_BASIS_CURRENTPRICE:
                            $percentage = $priceFactorValue / 100 * $nettoSum;
                            break;
                    }

                    // quiqqer/order#55
                    if ($nettoSum + $percentage <= 0) {
                        $percentage = $percentage - ($nettoSum + $percentage);
                    }

                    $PriceFactor->setNettoSum($percentage);

                    $nettoSum       = $this->round($nettoSum + $percentage);
                    $priceFactorSum = $priceFactorSum + $percentage;
                    break;

                default:
                    continue 2;
            }

            // add price factor VAT
            if (!($PriceFactor instanceof QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface)) {
                $Vat = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
            } else {
                try {
                    $VatType = $PriceFactor->getVatType();

                    if (!$VatType) {
                        throw new QUI\Exception('placeholder exception');
                    }

                    $Vat = QUI\ERP\Tax\Utils::getTaxEntry($VatType, $Area);
                } catch (QUI\Exception $Exception) {
                    $Vat = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
                }
            }

            $vatSum = $PriceFactor->getNettoSum() * ($Vat->getValue() / 100);
            $vat    = $Vat->getValue();

            $PriceFactor->setVat($vat);

            if ($isNetto) {
                $PriceFactor->setSum($PriceFactor->getNettoSum());
            } else {
                $PriceFactor->setSum($vatSum + $PriceFactor->getNettoSum());
            }

            if (!$Vat->isVisible()) {
                continue;
            }

            if (!isset($vatArray[$vat])) {
                $vatArray[$vat] = [
                    'vat'     => $vat,
                    'text'    => ErpCalc::getVatText($Vat->getValue(), $this->getUser(), $Locale),
                    'visible' => $Vat->isVisible()
                ];

                $vatArray[$vat]['sum'] = 0;
            }

            $vatArray[$vat]['sum'] = $vatArray[$vat]['sum'] + $vatSum;
        }

        // vat text
        $vatLists  = [];
        $vatText   = [];
        $bruttoSum = $nettoSum;

        foreach ($vatArray as $vatEntry) {
            $vatLists[$vatEntry['vat']] = true; // liste für MWST texte

            $bruttoSum = $bruttoSum + $vatEntry['sum'];
        }

        foreach ($vatLists as $vat => $bool) {
            $vatText[$vat] = ErpCalc::getVatText($vat, $this->getUser(), $Locale);
        }

        if ($this->ignoreVatCalculation) {
            $vatArray = [];
            $vatText  = [];
        }

        // delete 0 % vat, 0% vat is allowed to calculate more easily
        if (isset($vatText[0])) {
            unset($vatText[0]);
        }

        if (isset($vatArray[0])) {
            unset($vatArray[0]);
        }

        // gegenrechnung, wegen rundungsfehler
        if ($isNetto === false) {
            $priceFactorBruttoSums = 0;

            foreach ($priceFactors as $Factor) {
                /* @var $Factor QUI\ERP\Products\Utils\PriceFactor */
                $priceFactorBruttoSums = $priceFactorBruttoSums + \round($Factor->getSum(), 2);
            }

            $priceFactorBruttoSum = $subSum + $priceFactorBruttoSums;

            if ($priceFactorBruttoSum !== \round($bruttoSum, 2)) {
                $diff = $priceFactorBruttoSum - \round($bruttoSum, 2);

                // if we have a diff, we change the first vat price factor
                foreach ($priceFactors as $Factor) {
                    if ($Factor instanceof QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface) {
                        $Factor->setSum(\round($Factor->getSum() - $diff, 2));
                        $bruttoSum = \round($bruttoSum, 2);
                        break;
                    }
                }
            }
        }

        if ($bruttoSum <= 0 || $nettoSum <= 0) {
            $bruttoSum = 0;
            $nettoSum  = 0;

            foreach ($vatArray as $vat => $entry) {
                $vatArray[$vat]['sum'] = 0;
            }
        }

        $callback([
            'sum'          => $bruttoSum,
            'subSum'       => $subSum,
            'nettoSum'     => $nettoSum,
            'nettoSubSum'  => $nettoSubSum,
            'vatArray'     => $vatArray,
            'vatText'      => $vatText,
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => $this->getCurrency()->toArray()
        ]);

        return $List;
    }

    /**
     * Calculate the product price
     * only fields
     *
     * @param UniqueProduct $Product
     * @param callable|boolean $callback - optional, callback function for the calculated data array
     * @param null|QUI\ERP\Products\Field\Types\Price $Price - optional, price object to calc with
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getProductPrice(
        UniqueProduct $Product,
        $callback = false,
        $Price = null
    ) {
        // calc data
        if (!\is_callable($callback)) {
            $Product->calc($this);

            return $Product->getPrice();
        }

        $isNetto     = QUI\ERP\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area        = QUI\ERP\Utils\User::getUserArea($this->getUser());

        $nettoPrice   = $Product->getNettoPrice()->value();
        $priceFactors = $Product->getPriceFactors()->sort();

        if ($Price) {
            $nettoPrice = $Price->getValue();
        }

        if (empty($nettoPrice)) {
            $nettoPrice = 0;
        }

        $factors                    = [];
        $basisNettoPrice            = $nettoPrice;
        $calculationBasisBruttoList = [];

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

                case ErpCalc::CALCULATION_COMPLETE:
                    $nettoPrice     = $PriceFactor->getValue();
                    $priceFactorSum = 0;
                    $factors[]      = $PriceFactor->toArray();
                    break;

                // Prozent Angabe
                case ErpCalc::CALCULATION_PERCENTAGE:
                    $value = $PriceFactor->getValue();

                    switch ($PriceFactor->getCalculationBasis()) {
                        default:
                        case ErpCalc::CALCULATION_BASIS_NETTO:
                            $priceFactorSum = $value / 100 * $basisNettoPrice;
                            break;

                        case ErpCalc::CALCULATION_BASIS_CURRENTPRICE:
                            $priceFactorSum = $value / 100 * $nettoPrice;
                            break;
                    }
            }

            // quiqqer/order#55
            if ($nettoPrice + $priceFactorSum < 0) {
                $priceFactorSum = $priceFactorSum - ($nettoPrice + $priceFactorSum);
            }

            $PriceFactor->setNettoSum(
                \floatval($priceFactorSum * $Product->getQuantity())
            );

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
                    $PriceFactor->setNettoSum($PriceFactor->getValue());
                    break;

                // Prozent Angabe
                case ErpCalc::CALCULATION_PERCENTAGE:
                    $percentage = $PriceFactor->getValue() / 100 * $nettoPrice;
                    $nettoPrice = $nettoPrice + $percentage;
                    $PriceFactor->setNettoSum($percentage);
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
                'Product Vat ist nicht für den Benutzer gültig',
                'quiqqer/products'
            );
        }

        $vatValue   = $Vat->getValue();
        $nettoPrice = \floatval($nettoPrice);

        if (empty($vatValue) || empty($nettoPrice)) {
            $vatSum = 0;
        } else {
            $vatSum = $nettoPrice * ($vatValue / 100);
        }

        $bruttoPrice = $this->round($nettoPrice + $vatSum);

        // sum
        $nettoSum  = $this->round($nettoPrice * $Product->getQuantity());
        $vatSum    = $nettoSum * ($Vat->getValue() / 100);
        $bruttoSum = $this->round($nettoSum + $vatSum);

        $price      = $isNetto ? $nettoPrice : $bruttoPrice;
        $sum        = $isNetto ? $nettoSum : $bruttoSum;
        $basisPrice = $isNetto ? $basisNettoPrice : \floatval($basisNettoPrice) + (\floatval($basisNettoPrice) * \floatval($Vat->getValue()) / 100);

        $vatArray = [
            'vat'  => $Vat->getValue(),
            'sum'  => $this->round($nettoSum * ($Vat->getValue() / 100)),
            'text' => ErpCalc::getVatText($Vat->getValue(), $this->getUser())
        ];

        if (!$Vat->isVisible()) {
            $vatArray = [];
        }


        QUI\ERP\Debug::getInstance()->log(
            'Kalkulierter Produkt Preis '.$Product->getId(),
            'quiqqer/products'
        );

        QUI\ERP\Debug::getInstance()->log([
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
        ], 'quiqqer/products');


        $callback([
            'basisPrice'   => $basisPrice,
            'price'        => $price,
            'sum'          => $sum,
            'nettoSum'     => $nettoSum,
            'nettoPrice'   => $nettoPrice,
            'vatArray'     => $vatArray,
            'vatText'      => !empty($vatArray) ? $vatArray['text'] : '',
            'isEuVat'      => $isEuVatUser,
            'isNetto'      => $isNetto,
            'currencyData' => $this->getCurrency()->toArray(),
            'factors'      => $factors
        ]);

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
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
     */
    public function getVatTextByUser()
    {
        return ErpCalc::getVatText(
            QUI\ERP\Tax\Utils::getTaxByUser($this->getUser())->getValue(),
            $this->getUser()
        );
    }
}
