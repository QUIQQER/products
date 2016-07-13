<?php

namespace QUITests\ERP\Products\CaseStudies;

use QUI;
use QUITests\ERP\Products\CaseStudies\Classes\NettoUser;
use QUITests\ERP\Products\CaseStudies\Classes\ProductListHelper;

require_once dirname(__FILE__) . '/Classes/NettoUser.php';
require_once dirname(__FILE__) . '/Classes/ProductListHelper.php';

/**
 * Class NettoUserTest
 */
class NettoUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCasetStudyNetto()
    {
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage('      Netto Nutzer');
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage();

        $Brutto = new NettoUser();
        $List   = ProductListHelper::getList();

        $List->setUser($Brutto);
        $List->calc();

        ProductListHelper::outputList($List);
    }
}
