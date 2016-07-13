<?php

namespace QUITests\ERP\Products\CaseStudies;

use QUI;
use QUITests\ERP\Products\CaseStudies\Classes\CompanyUser;
use QUITests\ERP\Products\CaseStudies\Classes\ProductListHelper;

require_once dirname(__FILE__) . '/Classes/CompanyUser.php';
require_once dirname(__FILE__) . '/Classes/ProductListHelper.php';

/**
 * Class NettoUserTest
 */
class CompanyUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCasetStudyCompany()
    {
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage('      Company Nutzer');
        writePhpUnitMessage('/*********************************/');
        writePhpUnitMessage();

        $Company = new CompanyUser();
        $List    = ProductListHelper::getList($Company);
        $List->calc();

        ProductListHelper::outputList($List);
    }
}
