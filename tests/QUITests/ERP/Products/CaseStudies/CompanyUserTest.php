<?php

namespace QUITests\ERP\Products\CaseStudies;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUITests\ERP\Products\CaseStudies\Classes\CompanyUser;
use QUITests\ERP\Products\CaseStudies\Classes\ProductListHelper;

require_once dirname(__FILE__) . '/Classes/CompanyUser.php';
require_once dirname(__FILE__) . '/Classes/ProductListHelper.php';

/**
 * Class NettoUserTest
 */
class CompanyUserTest extends TestCase
{
    /**
     * @throws Exception
     * @throws \QUI\ERP\Products\Product\Exception
     */
    public function testCaseStudyCompany(): void
    {
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage('      Company Nutzer');
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage();

        $Company = new CompanyUser();
        $List = ProductListHelper::getList($Company);
        $List->calc();

        ProductListHelper::outputList($List);
    }
}
