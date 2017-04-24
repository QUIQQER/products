<?php

namespace QUITests\ERP\Products\CaseStudies;

use QUI;
use QUITests\ERP\Products\CaseStudies\Classes\BruttoUser;
use QUITests\ERP\Products\CaseStudies\Classes\ProductListHelper;

require_once dirname(__FILE__) . '/Classes/BruttoUser.php';
require_once dirname(__FILE__) . '/Classes/ProductListHelper.php';

/**
 * Class BruttoUserTest
 */
class BruttoUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCaseStudyBrutto()
    {
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage('      Brutto Nutzer');
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage();

        $Brutto = new BruttoUser();
        $List   = ProductListHelper::getList($Brutto);
        $List->calc();

        ProductListHelper::outputList($List);
    }

    public function testIsBruttoUser()
    {
        $this->assertFalse(
            QUI\ERP\Utils\User::isNettoUser(
                new BruttoUser()
            )
        );
    }
}
