<?php

namespace QUITests\ERP\Products\Integration\Handler;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Types\Date;
use QUI\ERP\Products\Field\Types\Price;
use QUI\ERP\Products\Handler\Fields;

class FieldsTest extends TestCase
{
    public function testRegisteredFieldTypesContainCoreProductTypes(): void
    {
        $registeredClasses = array_column(Fields::getFieldTypes(), 'src');

        self::assertContains(Date::class, $registeredClasses);
        self::assertContains(Price::class, $registeredClasses);
    }
}
