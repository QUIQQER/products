<?php

namespace QUITests\ERP\Products\CaseStudies;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUITests\ERP\Products\CaseStudies\Classes\BruttoUser;
use QUITests\ERP\Products\CaseStudies\Classes\ProductListHelper;

require_once dirname(__FILE__) . '/Classes/BruttoUser.php';
require_once dirname(__FILE__) . '/Classes/ProductListHelper.php';

/**
 * Class BruttoUserTest
 */
class BruttoUserTest extends TestCase
{
    public function testCaseStudyBrutto(): void
    {
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage('      Brutto Nutzer');
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage();

        $Brutto = new BruttoUser();
        $List = ProductListHelper::getList($Brutto);
        $List->calc();

        ProductListHelper::outputList($List);
    }

    public function testIsBruttoUser(): void
    {
        $this->assertFalse(
            QUI\ERP\Utils\User::isNettoUser(
                new BruttoUser()
            )
        );
    }
}
