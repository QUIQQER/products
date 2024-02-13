<?php

/**
 * This file contains QUI\ERP\Products\Utils\Calc
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Products\Field\Types\Vat;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\ERP\Tax\TaxEntry;
use QUI\ERP\Tax\TaxType;
use QUI\ERP\Tax\Utils as TaxUtils;
use QUI\Interfaces\Users\User as UserInterface;

use function count;
use function floatval;
use function key;
use function round;

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
    public function getCurrency(): ?QUI\ERP\Currency\Currency
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
    public function calcProductList(ProductList $List, $callback = false): ProductList
    {
        // calc data
        if (!\is_callable($callback)) {
            return $List->calc();
        }

        // user order address
        $Order = $List->getOrder();
        $CurrentAddress = $this->getUser()->getAttribute('CurrentAddress');
        $recalculateProducts = false;

        if ($Order) {
            $DeliveryAddress = $Order->getDeliveryAddress();


            if ($DeliveryAddress->getId() && $Order->getDeliveryAddress() !== $CurrentAddress) {
                $recalculateProducts = true;
            }


            if ($DeliveryAddress->getId()) {
                QUI\ERP\Utils\User::setUserCurrentAddress(
                    $this->getUser(),
                    $DeliveryAddress
                );
            }
        }

        $products = $List->getProducts();
        $isNetto = QUI\ERP\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Locale = QUI\ERP\Products\Handler\Products::getLocale();

        $Area = QUI\ERP\Utils\User::getUserArea($this->getUser());
        $DefaultArea = QUI\ERP\Defaults::getArea();

        // user order address
        $Order = $List->getOrder();

        if ($Order) {
            try {
                $DeliveryAddress = $Order->getDeliveryAddress();
                $DeliveryArea = QUI\ERP\Areas\Utils::getAreaByCountry($DeliveryAddress->getCountry());

                if ($DeliveryArea) {
                    $Area = $DeliveryArea;
                } else {
                    $Area = $DefaultArea;
                }
            } catch (QUI\Exception $Exception) {
            }
        }


        if ($this->ignoreVatCalculation) {
            $isNetto = true;
        }

        $subSum = 0;
        $nettoSum = 0;
        $vatArray = [];

        $Currency = $this->getCurrency();
        $precision = $Currency->getPrecision();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            // add netto price
            try {
                QUI::getEvents()->fireEvent(
                    'onQuiqqerProductsCalcListProduct',
                    [$this, $Product]
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::write(
                    $Exception->getMessage(),
                    QUI\System\Log::LEVEL_ERROR,
                    $Exception->getContext()
                );
            }

            if ($recalculateProducts) {
                $Product->recalculation();
            }

            $this->getProductPrice($Product);

            $productAttributes = $Product->getAttributes();

            $subSum = $subSum + $productAttributes['calculated_sum'];
            $nettoSum = $nettoSum + $productAttributes['calculated_nettoSum'];

            $productVatArray = $productAttributes['calculated_vatArray'];

            if (!isset($productVatArray['vat'])) {
                continue;
            }

            $vat = $productVatArray['vat'];

            if (!isset($vatArray[(string)$vat])) {
                $vatArray[(string)$vat] = $productVatArray;
                $vatArray[(string)$vat]['sum'] = 0;
            }

            $vatArray[(string)$vat]['sum'] = $vatArray[(string)$vat]['sum'] + $productVatArray['sum'];
        }

//        $subSum   = \round($subSum, $Currency->getPrecision());
//        $nettoSum = \round($nettoSum, $Currency->getPrecision());

        QUI\ERP\Debug::getInstance()->log('Berechnete Produktliste MwSt', 'quiqqer/product');
        QUI\ERP\Debug::getInstance()->log($vatArray, 'quiqqer/product');

        try {
            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcList',
                [$this, $List, $nettoSum]
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::write(
                $Exception->getMessage(),
                QUI\System\Log::LEVEL_ERROR,
                $Exception->getContext()
            );
        }

        // price factors
        $priceFactors = $List->getPriceFactors()->sort();
        $nettoSubSum = $nettoSum;
        $priceFactorSum = 0;


        /* @var $PriceFactor PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            if ($PriceFactor->getCalculationBasis() === ErpCalc::CALCULATION_GRAND_TOTAL) {
                $PriceFactor->setNettoSum($PriceFactor->getValue());
                $PriceFactor->setSum($PriceFactor->getValue());
                $PriceFactor->setValue($PriceFactor->getValue());
                $PriceFactor->setValueText(0);
                $PriceFactor->setVat(0);
                continue;
            }

            $priceFactorValue = $PriceFactor->getValue();
            $Vat = null;

            // find out the vat of the price factor
            if (!($PriceFactor instanceof QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface)) {
                $vatValue = $PriceFactor->getVat();

                if ($vatValue === false) {
                    $Vat = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
                    $vatValue = $Vat->getValue();
                }
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

                $vatValue = $Vat->getValue();
            }

            if ($Vat === null) {
                $Vat = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
            }

            if ($isEuVatUser) {  //|| $PriceFactor->getAttribute('class') === 'QUI\ERP\Accounting\Invoice\Articles\Text') {
                $vatValue = 0;
            }

            switch ($PriceFactor->getCalculation()) {
                // einfache Zahl, Währung --- kein Prozent
                case ErpCalc::CALCULATION_COMPLEMENT:
                    // quiqqer/order#55
                    if ($nettoSum + $priceFactorValue <= 0) {
                        $priceFactorValue = $priceFactorValue - ($nettoSum + $priceFactorValue);
                    }

                    $nettoSum = $nettoSum + $priceFactorValue;
                    $priceFactorSum = $priceFactorSum + $priceFactorValue;
                    $priceFactorValue = round($priceFactorValue, $Currency->getPrecision());

                    $PriceFactor->setNettoSum($priceFactorValue);

                    if (!$isNetto && !$PriceFactor->hasValueText()) {
                        $vCalc = $Vat->getValue() / 100 + 1;
                        $bruttoDisplay = round($priceFactorValue * $vCalc, $Currency->getPrecision());
                        $bruttoDisplay = $Currency->format($bruttoDisplay);

                        $PriceFactor->setValueText($bruttoDisplay);
                    }

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

                        case ErpCalc::CALCULATION_BASIS_VAT_BRUTTO:
                            if ($isNetto) {
                                $bruttoSubSum = $subSum * ($vatValue / 100 + 1);
                                $percentage = $priceFactorValue / 100 * $bruttoSubSum;
                            } else {
                                $percentage = $priceFactorValue / 100 * $subSum;
                            }
                            break;

                        case ErpCalc::CALCULATION_GRAND_TOTAL:
                            continue 3;
                    }

                    // quiqqer/order#55
                    if ($nettoSum + $percentage <= 0) {
                        $percentage = $percentage - ($nettoSum + $percentage);
                    }

                    // calc price factor vat
                    if (
                        !$isNetto &&
                        $vatValue &&
                        $PriceFactor->getCalculationBasis() === ErpCalc::CALCULATION_BASIS_VAT_BRUTTO
                    ) {
                        $percentage = $percentage / ($vatValue / 100 + 1);
                    }

                    $PriceFactor->setNettoSum($percentage);

                    $nettoSum = round($nettoSum + $percentage, $Currency->getPrecision());
                    $priceFactorSum = $priceFactorSum + $percentage;
                    break;

                default:
                    continue 2;
            }

            $vatSum = $PriceFactor->getNettoSum() * ($vatValue / 100);
            $vatSumRounded = round($vatSum, $Currency->getPrecision());

            $PriceFactor->setVat($vatValue);

            if ($isNetto) {
                $PriceFactor->setSum(
                    round($PriceFactor->getNettoSum(), $Currency->getPrecision())
                );
            } else {
                $vatBruttoSum = $vatSumRounded + $PriceFactor->getNettoSum();

                $PriceFactor->setSum($vatBruttoSum);
            }

            if ($Vat && !$Vat->isVisible()) {
                continue;
            }

            if (!isset($vatArray[(string)$vatValue]) && $Vat) {
                $vatArray[(string)$vatValue] = [
                    'vat' => $vatValue,
                    'text' => ErpCalc::getVatText($Vat->getValue(), $this->getUser(), $Locale),
                    'visible' => $Vat->isVisible()
                ];

                $vatArray[(string)$vatValue]['sum'] = 0;
            }

            $vatArray[(string)$vatValue]['sum'] = $vatArray[(string)$vatValue]['sum'] + $vatSum;
        }

        // vat text
        $vatLists = [];
        $vatText = [];

        $nettoSum = round($nettoSum, $precision);
        $nettoSubSum = round($nettoSubSum, $precision);
        $subSum = round($subSum, $precision);
        $bruttoSum = $nettoSum;

        foreach ($vatArray as $vatEntry) {
            $vatLists[(string)$vatEntry['vat']] = true; // liste für MWST texte

            $bruttoSum = $bruttoSum + $vatEntry['sum'];
        }

        $bruttoSum = round($bruttoSum, $Currency->getPrecision());

        foreach ($vatLists as $vat => $bool) {
            $vatText[$vat] = ErpCalc::getVatText($vat, $this->getUser(), $Locale);
        }

        if ($this->ignoreVatCalculation) {
            $vatArray = [];
            $vatText = [];
        }


        // extra methode für quiqqer/erp#20 hier einführen,
        // dann kann dies mehrmals hier ausgeführt werden


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
                if ($Factor->getCalculationBasis() !== ErpCalc::CALCULATION_GRAND_TOTAL) {
                    $priceFactorBruttoSums = $priceFactorBruttoSums + $Factor->getSum();
                }
            }

            $priceFactorBruttoSum = $subSum + $priceFactorBruttoSums;
            $bruttoSum = $priceFactorBruttoSum;

            // counterbalance - gegenrechnung
            // works only for one vat entry
            if (count($vatArray) === 1) {
                $vat = key($vatArray);
                $netto = $bruttoSum / ($vat / 100 + 1);

                $vatSum = $bruttoSum - $netto;
                $vatSum = round($vatSum, $Currency->getPrecision());
                $diff = abs($vatArray[(string)$vat]['sum'] - $vatSum);

                if ($diff <= 0.019) {
                    $vatArray[(string)$vat]['sum'] = $vatSum;
                }
            }
        }

        if ($bruttoSum <= 0 || $nettoSum <= 0) {
            $bruttoSum = 0;
            $nettoSum = 0;

            foreach ($vatArray as $vat => $entry) {
                $vatArray[(string)$vat]['sum'] = 0;
            }
        }

        // look if CALCULATION_GRAND_TOTAL
        $grandSubSum = $bruttoSum;

        foreach ($priceFactors as $Factor) {
            if ($Factor->getCalculationBasis() === ErpCalc::CALCULATION_GRAND_TOTAL) {
                $value = $Factor->getValue();
                $bruttoSum = $bruttoSum + $value;

                if ($bruttoSum < 0) {
                    $bruttoSum = 0;
                }
            }
        }


        $callback([
            'sum' => $bruttoSum,
            'grandSubSum' => $grandSubSum,
            'subSum' => $subSum,
            'nettoSum' => $nettoSum,
            'nettoSubSum' => $nettoSubSum,
            'vatArray' => $vatArray,
            'vatText' => $vatText,
            'isEuVat' => $isEuVatUser,
            'isNetto' => $isNetto,
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
     * @param bool $ignorePriceFactors - ignore price factors, default = false
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getProductPrice(
        UniqueProduct $Product,
        $callback = false,
        $Price = null,
        bool $ignorePriceFactors = false
    ): QUI\ERP\Money\Price {
        // calc data
        if (!\is_callable($callback)) {
            $Product->calc($this);

            return $Product->getPrice();
        }

        try {
            QUI::getEvents()->fireEvent(
                'onQuiqqerProductsCalcProduct',
                [$this, $Product]
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::write(
                $Exception->getMessage(),
                QUI\System\Log::LEVEL_ERROR,
                $Exception->getContext()
            );
        }

        $isNetto = QUI\ERP\Utils\User::isNettoUser($this->getUser());
        $isEuVatUser = QUI\ERP\Tax\Utils::isUserEuVatUser($this->getUser());
        $Area = QUI\ERP\Utils\User::getUserArea($this->getUser());
        $Currency = $this->getCurrency();

        $nettoPrice = $Product->getNettoPrice()->value();
        $priceFactors = $Product->getPriceFactors()->sort();

        if ($Price) {
            $nettoPrice = $Price->getValue();
        }

        if (empty($nettoPrice)) {
            $nettoPrice = 0;
        }

        $nettoPriceNotRounded = $nettoPrice;
        $nettoPrice = round($nettoPrice, $Currency->getPrecision());

        $factors = [];
        $basisNettoPrice = $nettoPrice;
        $calculationBasisBruttoList = [];

        /* @var PriceFactor $PriceFactor */
        if ($ignorePriceFactors === false) {
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
                        $nettoPrice = $PriceFactor->getValue();
                        $nettoPriceNotRounded = $PriceFactor->getValue();

                        $priceFactorSum = 0;
                        $factors[] = $PriceFactor->toArray();
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
                    floatval($priceFactorSum * $Product->getQuantity())
                );

                $nettoPrice = $nettoPrice + $priceFactorSum;
                $nettoPriceNotRounded = $nettoPriceNotRounded + $priceFactorSum;
                $priceFactorArray = $PriceFactor->toArray();

                $priceFactorArray['sum'] = $priceFactorSum;

                $factors[] = $priceFactorArray;
            }
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
                $TaxType = new QUI\ERP\Tax\TaxType($Tax->getValue());
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            } catch (QUI\Exception $Exception) {
                QUI\ERP\Debug::getInstance()->log($Exception, 'quiqqer/products');
                continue;
            }

            // steuern auf netto preis addieren
            $taxNettoPrice = $this->round($nettoPrice * ($TaxEntry->getValue() / 100));
            $nettoPrice = $nettoPrice + $taxNettoPrice;
        }


        // MwSt / VAT
        if ($isEuVatUser || $Product->getAttribute('class') === 'QUI\ERP\Accounting\Invoice\Articles\Text') {
            $Vat = new QUI\ERP\Tax\TaxEntryEmpty();
        } else {
            $Vat = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());

            // Wenn Produkt eigene VAT gesetzt hat und diese zum Benutzer passt
            $ProductVat = $Product->getField(FieldHandler::FIELD_VAT);

            try {
                $TaxType = new QUI\ERP\Tax\TaxType($ProductVat->getValue());
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);

                if ($TaxEntry->isActive()) {
                    $Vat = $TaxEntry;
                }
            } catch (QUI\Exception $Exception) {
                QUI\ERP\Debug::getInstance()->log(
                    'Product Vat ist nicht für den Benutzer gültig',
                    'quiqqer/products'
                );
            }
        }

        $vatValue = $Vat->getValue();
        $nettoPrice = floatval($nettoPrice);

        if (empty($vatValue) || empty($nettoPrice)) {
            $vatSum = 0;
        } else {
            $vatSum = $nettoPrice * ($vatValue / 100);
            $vatSum = round($vatSum, $Currency->getPrecision());
        }


        // price factor display with empty value text
        // for brutto user -> brutto display values
        if (!$isNetto) {
            foreach ($priceFactors as $PriceFactor) {
                if ($PriceFactor->hasValueText()) {
                    continue;
                }

                $vCalc = $Vat->getValue() / 100 + 1;
                $netto = $PriceFactor->getValue();

                if ($PriceFactor->getCalculation() !== QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE) {
                    $bruttoDisplay = round($netto * $vCalc, $Currency->getPrecision());
                    $bruttoDisplay = $Currency->format($bruttoDisplay);

                    $PriceFactor->setValueText($bruttoDisplay);
                }
            }
        }


        if (!$isNetto) {
            // korrektur rechnung / 1 cent problem
            $checkVatBrutto = $nettoPriceNotRounded * ($vatValue / 100 + 1);
            $checkVat = $checkVatBrutto - $nettoPriceNotRounded;
            $checkVatBrutto = round($checkVatBrutto, $Currency->getPrecision());
            $checkVat = round($checkVat * $Product->getQuantity(), $Currency->getPrecision());

            $bruttoPrice = $this->round($nettoPrice + $vatSum);

            // sum
            $nettoSum = $this->round($nettoPrice * $Product->getQuantity());
            $nettoSumNotRounded = $nettoPriceNotRounded * $Product->getQuantity();

            $vatSum = round($nettoSumNotRounded * ($Vat->getValue() / 100), $Currency->getPrecision());

            // korrektur rechnung / 1 cent problem
            if ($checkVatBrutto !== $bruttoPrice) {
                $vatSum = $checkVat;
                $bruttoPrice = $checkVatBrutto;
            }

            // if the user is brutto
            // and we have a quantity
            // we need to calc first the brutto product price of one product
            // -> because of 1 cent rounding error
            $bruttoSum = $bruttoPrice * $Product->getQuantity();
        } else {
            // sum
            $nettoSum = $this->round($nettoPrice * $Product->getQuantity());
            $vatSum = round($nettoSum * ($Vat->getValue() / 100), $Currency->getPrecision());
            $bruttoSum = $this->round($nettoSum + $vatSum);

            $nettoSumNotRounded = $nettoPriceNotRounded * $Product->getQuantity();
        }


        $price = $isNetto ? $nettoPrice : $bruttoPrice;
        $sum = $isNetto ? $nettoSum : $bruttoSum;

        if ($isNetto) {
            $basisPrice = $basisNettoPrice;
        } else {
            $basisPrice = floatval($basisNettoPrice)
                + (floatval($basisNettoPrice) * floatval($Vat->getValue()) / 100);

            $basisPrice = round($basisPrice, $Currency->getPrecision());
        }

        $vatArray = [
            'vat' => $Vat->getValue(),
            'sum' => $vatSum,
            'text' => ErpCalc::getVatText($Vat->getValue(), $this->getUser())
        ];

        if (!$Vat->isVisible()) {
            $vatArray = [];
        }


        QUI\ERP\Debug::getInstance()->log(
            'Kalkulierter Produkt Preis ' . $Product->getId(),
            'quiqqer/products'
        );

        QUI\ERP\Debug::getInstance()->log([
            'nettoPriceNotRounded' => $nettoPriceNotRounded,
            'basisPrice' => $basisPrice,
            'price' => $price,
            'sum' => $sum,
            'nettoSum' => $nettoSum,
            'nettoSumNotRounded' => $nettoSumNotRounded,
            'nettoPrice' => $nettoPrice,
            'vatArray' => $vatArray,
            'vatText' => !empty($vatArray) ? $vatArray['text'] : '',
            'isEuVat' => $isEuVatUser,
            'isNetto' => $isNetto,
            'currencyData' => $this->getCurrency()->toArray(),
            'factors' => $factors
        ], 'quiqqer/products');


        $callback([
            'nettoPriceNotRounded' => $nettoPriceNotRounded,
            'basisPrice' => $basisPrice,
            'price' => $price,
            'sum' => $sum,
            'nettoSum' => $nettoSum,
            'nettoSumNotRounded' => $nettoSumNotRounded,
            'nettoPrice' => $nettoPrice,
            'vatArray' => $vatArray,
            'vatText' => !empty($vatArray) ? $vatArray['text'] : '',
            'isEuVat' => $isEuVatUser,
            'isNetto' => $isNetto,
            'currencyData' => $this->getCurrency()->toArray(),
            'factors' => $factors
        ]);

        return $Product->getPrice();
    }

    /**
     * Rounds the value via shop config
     *
     * @param string $value
     * @return float|mixed
     */
    public function round(string $value): float
    {
        return QUI\ERP\Accounting\Calc::getInstance($this->getUser())->round($value);
    }

    /**
     * Calc the price in dependence of the user
     *
     * @param int|float $nettoPrice - netto price
     * @return int|float
     *
     * @throws QUI\Exception
     */
    public function getPrice($nettoPrice)
    {
        if (empty($nettoPrice)) {
            return 0;
        }

        $isNetto = QUI\ERP\Utils\User::isNettoUser($this->getUser());

        if ($isNetto) {
            return $nettoPrice;
        }

        $Tax = QUI\ERP\Tax\Utils::getTaxByUser($this->getUser());
        $vatSum = $nettoPrice * ($Tax->getValue() / 100);

        return $this->round($nettoPrice + $vatSum);
    }

    /**
     * @param $price
     * @param $formatted
     * @param $productId - optional, id of the product
     *
     * @return float|int|string|null
     *
     * @throws QUI\Exception
     */
    public static function calcBruttoPrice($price, $formatted, $productId = false)
    {
        $price = QUI\ERP\Money\Price::validatePrice($price);
        $Area = QUI\ERP\Defaults::getArea();
        $TaxEntry = null;
        $Currency = QUI\ERP\Defaults::getCurrency();

        if (!empty($productId)) {
            $Product = Products::getProduct((int)$productId);

            /* @var $Field Vat */
            $Vat = $Product->getField(FieldHandler::FIELD_VAT);

            try {
                $TaxType = new QUI\ERP\Tax\TaxType($Vat->getValue());
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            } catch (QUI\Exception $Exception) {
            }
        }

        if (!$TaxEntry) {
            $TaxType = TaxUtils::getTaxTypeByArea($Area);

            if ($TaxType instanceof TaxType) {
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            } elseif ($TaxType instanceof TaxEntry) {
                $TaxEntry = $TaxType;
            } else {
                if (isset($formatted) && $formatted) {
                    return $Currency->format($price);
                }

                return $price;
            }
        }

        $vat = $TaxEntry->getValue();
        $vat = (100 + $vat) / 100;

        $price = $price * $vat;
        $price = round($price, $Currency->getPrecision());

        if (isset($formatted) && $formatted) {
            return $Currency->format($price);
        }

        return $price;
    }

    /**
     * @param $price
     * @param $formatted
     * @param $productId - optional, id of the product
     *
     * @return float|int|string|null
     *
     * @throws QUI\Exception
     */
    public static function calcNettoPrice($price, $formatted, $productId)
    {
        $price = QUI\ERP\Money\Price::validatePrice($price);
        $Area = QUI\ERP\Defaults::getArea();
        $TaxEntry = null;

        if (!empty($productId)) {
            $Product = Products::getProduct((int)$productId);

            /* @var $Field Vat */
            $Vat = $Product->getField(FieldHandler::FIELD_VAT);

            try {
                $TaxType = new QUI\ERP\Tax\TaxType($Vat->getValue());
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            } catch (QUI\Exception $Exception) {
            }
        }

        if (!$TaxEntry) {
            $TaxType = TaxUtils::getTaxTypeByArea($Area);

            if ($TaxType instanceof TaxType) {
                $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);
            } elseif ($TaxType instanceof TaxEntry) {
                $TaxEntry = $TaxType;
            } else {
                if (isset($formatted) && $formatted) {
                    return QUI\ERP\Defaults::getCurrency()->format($price);
                }

                return $price;
            }
        }

        $vat = $TaxEntry->getValue();
        $vat = ($vat / 100) + 1;

        $price = $price / $vat;

        if (isset($formatted) && $formatted) {
            return QUI\ERP\Defaults::getCurrency()->format($price);
        }

        return $price;
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
    public function getVatTextByUser(): string
    {
        return ErpCalc::getVatText(
            QUI\ERP\Tax\Utils::getTaxByUser($this->getUser())->getValue(),
            $this->getUser()
        );
    }
}
