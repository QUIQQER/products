<?php

/**
 * This file contains QUITests\ERP\Products\CaseStudies\Classes
 */
namespace QUITests\ERP\Products\CaseStudies\Classes;

use QUI;

/**
 * Class ProductListHelper
 * @package QUITests\ERP\Products\CaseStudies\Classes
 */
class ProductListHelper
{
    /**
     * Return the product test list
     *
     * @param QUI\Interfaces\Users\User $User
     * @return QUI\ERP\Products\Product\ProductList
     * @throws QUI\ERP\Products\Product\Exception
     */
    public static function getList($User)
    {
        $Products = new QUI\ERP\Products\Handler\Products();
        $List     = new QUI\ERP\Products\Product\ProductList(
            array('duplicate' => true)
        );

        $List->setUser($User);

        // preis 40 €
        $Product = $Products->getProduct(4575);

        // produkt 1
        $Product->getField(1013)->setValue(0);
        $Product1 = $Product->createUniqueProduct($User);
        $Product1->setQuantity(2);

        // produkt 2
        $Product->getField(1013)->setValue(1);
        $Product2 = $Product->createUniqueProduct($User);

        // produkt 3
        $Product->getField(1013)->setValue(1);
        $Product3 = $Product->createUniqueProduct($User);

        $List->addProduct($Product1);
        $List->addProduct($Product2);
        $List->addProduct($Product3);

        return $List;
    }

    /**
     * Ausgabe einer produkt liste für phpunit
     *
     * @param QUI\ERP\Products\Product\ProductList $List
     */
    public static function outputList(QUI\ERP\Products\Product\ProductList $List)
    {
        $data     = $List->toArray();
        $User     = $List->getUser();
        $Locale   = $User->getLocale();
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();

        writePhpUnitMessage('Preis Liste');
        writePhpUnitMessage('== ' . ($data['isNetto'] ? 'netto' : 'brutto') . ' =========================');
        writePhpUnitMessage();

        writePhpUnitMessage('Produkte');
        writePhpUnitMessage('------');
        writePhpUnitMessage();

        foreach ($data['products'] as $product) {
            $priceFactors = $product['calculated_factors'];

            writePhpUnitMessage($product['quantity'] . 'x ' . $product['title']);
            writePhpUnitMessage('-------');

            writePhpUnitMessage('Grundpreis: ' . $Currency->format($product['price_netto'], $Locale));
            writePhpUnitMessage();

            writePhpUnitMessage('    Calc Netto Sum: ' . $Currency->format($product['calculated_nettoSum'], $Locale));
            writePhpUnitMessage('    Calc Price: ' . $Currency->format($product['calculated_price'], $Locale));
            writePhpUnitMessage('    Calc Sum: ' . $Currency->format($product['calculated_sum'], $Locale));


            foreach ($priceFactors as $priceFactor) {
                writePhpUnitMessage(
                    '    -> ' . $priceFactor['title'] . ': ' . $Currency->format($priceFactor['sum'], $Locale)
                );
            }

            writePhpUnitMessage();

            foreach ($product['calculated_vatArray'] as $vatEntry) {
                writePhpUnitMessage(
                    '    -> Vat ' . $vatEntry['vat'] . '% : ' .
                    $vatEntry['text'] . ' ' .
                    $Currency->format($vatEntry['sum'], $Locale)
                );
            }

            writePhpUnitMessage();
        }

        writePhpUnitMessage();

        writePhpUnitMessage('NettoSubSum: ' . $Currency->format($data['nettoSubSum'], $Locale));
        writePhpUnitMessage('SubSum: ' . $Currency->format($data['subSum'], $Locale));
        writePhpUnitMessage();
        writePhpUnitMessage();

        // rabatte
        writePhpUnitMessage('Rabatte');
        writePhpUnitMessage('------');
        writePhpUnitMessage();

        $priceFactore = $List->getPriceFactors()->sort();

        /* @var QUI\ERP\Products\Utils\PriceFactor $PriceFactor */
        foreach ($priceFactore as $PriceFactor) {
            writePhpUnitMessage('    ' . $PriceFactor->getTitle() . ': ' . $PriceFactor->getValueFormated());
        }

        writePhpUnitMessage();
        writePhpUnitMessage();

        writePhpUnitMessage('Berechnung');
        writePhpUnitMessage('------');
        writePhpUnitMessage();

        writePhpUnitMessage('nettoSum: ' . $Currency->format($data['nettoSum'], $Locale));
        writePhpUnitMessage();
        writePhpUnitMessage('    MwSt.:');

        foreach ($data['vatArray'] as $vatEntry) {
            writePhpUnitMessage('    - ' . $vatEntry['text'] . ': ' . $Currency->format($vatEntry['sum'], $Locale));
        }

        writePhpUnitMessage();
        writePhpUnitMessage('Sum: ' . $Currency->format($data['sum'], $Locale));
        writePhpUnitMessage();
        writePhpUnitMessage();
    }
}
