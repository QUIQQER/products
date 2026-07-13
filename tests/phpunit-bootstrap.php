<?php

function writePhpUnitMessage(string $str = ''): void
{
    if (empty($str)) {
        return;
    }

    echo $str;
    echo PHP_EOL;
}

require_once __DIR__ . '/../../core/tests/phpunit-bootstrap.php';
require_once __DIR__ . '/Fixtures/TestUser.php';
require_once __DIR__ . '/Fixtures/RecordingCalc.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/IntegrationTestEnvironment.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Product/ProductTestHelper.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Product/ProductIntegrationTestCase.php';

QUITests\ERP\Products\Integration\Product\ProductTestHelper::registerCleanup();
