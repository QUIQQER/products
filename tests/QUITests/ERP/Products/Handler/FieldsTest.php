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

        $contains = function ($fields, $fieldType) {
            foreach ($fields as $fieldData) {
                if ($fieldData['src'] == $fieldType) {
                    return true;
                }
            }
            return false;
        };

        $this->assertTrue($contains($fields, 'QUI\ERP\Products\Field\Types\Date'));
        $this->assertTrue($contains($fields, 'QUI\ERP\Products\Field\Types\Price'));
    }
}
