<?php

namespace QUITests\ERP\Products\CaseStudies;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUITests\ERP\Products\CaseStudies\Classes\NettoUser;
use QUITests\ERP\Products\CaseStudies\Classes\ProductListHelper;

require_once dirname(__FILE__) . '/Classes/NettoUser.php';
require_once dirname(__FILE__) . '/Classes/ProductListHelper.php';

/**
 * Class NettoUserTest
 */
class NettoUserTest extends TestCase
{
    /**
     * @throws Exception
     * @throws \QUI\ERP\Products\Product\Exception
     */
    public function testCaseStudyNetto(): void
    {
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage('      Netto Nutzer');
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage();

        $Brutto = new NettoUser();
        $List = ProductListHelper::getList($Brutto);
        $List->calc();

        ProductListHelper::outputList($List);
    }
}
