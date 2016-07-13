<?php

namespace QUITests\ERP\Products\CaseStudies;

use QUI;
use QUITests\ERP\Products\CaseStudies\Classes\BruttoUser;

require_once dirname(__FILE__) . '/Classes/BruttoUser.php';

/**
 * Class BruttoUserTest
 */
class BruttoUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCaseStudyBrutto()
    {
        QUI::getLocale()->setCurrent('de');
        $Brutto = new BruttoUser();

        $Products = new QUI\ERP\Products\Handler\Products();
        $List     = new QUI\ERP\Products\Product\ProductList(
            array('duplicate' => true),
            $Brutto
        );

        // preis 40 €
        $Product = $Products->getProduct(4575);

        // produkt 1
        $Product->getField(1013)->setValue(0);
        $Product1 = $Product->createUniqueProduct();

        // produkt 2
        $Product->getField(1013)->setValue(1);
        $Product2 = $Product->createUniqueProduct();

        // produkt 3
        $Product->getField(1013)->setValue(1);
        $Product3 = $Product->createUniqueProduct();

        $List->addProduct($Product1);
        $List->addProduct($Product2);
        $List->addProduct($Product3);

        // calculation
        $List->calc();


        writePhpUnitMessage('Preis Liste');
        writePhpUnitMessage('===========================');
        writePhpUnitMessage();

        $data = $List->toArray();

        writePhpUnitMessage('Produkte');
        writePhpUnitMessage('------');
        writePhpUnitMessage();

        foreach ($data['products'] as $product) {
            writePhpUnitMessage($product['title']);
            writePhpUnitMessage('-------');

            writePhpUnitMessage('    Calc Netto Sum: ' . $product['calculated_nettoSum']);
            writePhpUnitMessage('    Calc Price: ' . $product['calculated_price']);
            writePhpUnitMessage('    Calc Sum: ' . $product['calculated_sum']);

            foreach ($product['calculated_vatArray'] as $vatEntry) {
                writePhpUnitMessage(
                    '    -> Vat ' . $vatEntry['vat'] . '% : ' . $vatEntry['sum'] . ' ' . $vatEntry['text']
                );
            }

            writePhpUnitMessage();
        }

        writePhpUnitMessage();

        writePhpUnitMessage('SubSum: ' . $data['subSum']);
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

        writePhpUnitMessage('isNetto: ' . ($data['isNetto'] ? 1 : 0));
        writePhpUnitMessage('nettoSum: ' . $data['nettoSum']);
        writePhpUnitMessage();
        writePhpUnitMessage('    MwSt.:');

        foreach ($data['vatArray'] as $vatEntry) {
            writePhpUnitMessage('    - ' . $vatEntry['text'] . ': ' . $vatEntry['sum']);
        }

        writePhpUnitMessage();
        writePhpUnitMessage('Sum: ' . $data['sum']);
        writePhpUnitMessage();
        writePhpUnitMessage();
    }

    public function testIsBruttoUser()
    {
        $this->assertFalse(
            QUI\ERP\Products\Utils\User::isNettoUser(
                new BruttoUser()
            )
        );
    }
}
