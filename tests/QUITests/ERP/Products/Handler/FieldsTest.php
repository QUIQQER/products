<?php

namespace QUITests\ERP\Products\Handler;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Exception;

/**
 * Class FieldsTest
 */
class FieldsTest extends TestCase
{
    /**
     * Create a child test
     * @throws Exception
     */
    public function testGetFieldTypes(): void
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
