<?php

namespace QUITests\ERP\Products\CaseStudies;

use QUI;

/**
 * Class BruttoUserTest
 */
class BruttoUserTest extends \PHPUnit_Framework_TestCase
{
    public function testCasetStudieBrutto()
    {
        $Brutto = $this->userDummy();

    }


    protected function userDummy()
    {
        return $this->getMockBuilder('\QUI\Users\User')
            ->setMethods(array('debug', 'info'))
            ->getMock();

    }
}
