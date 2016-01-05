<?php

namespace QUITests\ERP\Products\Handler;

use QUI;

/**
 * Class FieldsTest
 */
class FieldsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create child test
     * @throws \QUI\Exception
     */
    public function testGetFieldTypes()
    {
        $fields = QUI\ERP\Products\Handler\Fields::getFieldTypes();

        $this->assertContains("Date", $fields);
        $this->assertContains("Price", $fields);
    }
}
