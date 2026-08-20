<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

function writePhpUnitMessage(string $str = ''): void
{
    if (empty($str)) {
        return;
    }

    echo $str;
    echo PHP_EOL;
}

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/SqlitePlatform.php';
require_once __DIR__ . '/SqliteTestEnvironment.php';
require_once __DIR__ . '/stubs/QUI/ERP/DemoData/DemoDataStubs.php';
require_once __DIR__ . '/Fixtures/TestUser.php';
require_once __DIR__ . '/Fixtures/RecordingCalc.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/ProjectTestHelper.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/IntegrationTestEnvironment.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Product/ProductTestHelper.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Product/ProductIntegrationTestCase.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Ajax/AjaxTestCase.php';

QUI\System\TestCleanup::register();
QUITests\ERP\Products\SqliteTestEnvironment::activate();
QUITests\ERP\Products\Integration\Product\ProductTestHelper::registerCleanup();
