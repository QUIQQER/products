<?php

namespace QUITests\ERP\Products\Integration;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Areas\Area;
use ReflectionMethod;

class IntegrationTestEnvironmentTest extends TestCase
{
    protected function tearDown(): void
    {
        IntegrationTestEnvironment::cleanup();
    }

    public function testDefaultAreaFixtureDoesNotDependOnAreasLocaleEvents(): void
    {
        $Method = new ReflectionMethod(IntegrationTestEnvironment::class, 'createDefaultArea');
        $Area = $Method->invoke(null, 'ZZ');

        $this->assertInstanceOf(Area::class, $Area);
        $this->assertSame(
            'ZZ',
            QUI::getDataBaseConnection()->fetchOne(
                'SELECT countries FROM ' . QUI::getDBTableName('areas') . ' WHERE id = ?',
                [$Area->getId()]
            )
        );
    }
}
