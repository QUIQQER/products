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

require_once __DIR__ . '/DatabaseEnvironment.php';
require_once __DIR__ . '/../../../../bootstrap.php';

QUI::getLocale()->setCurrent('de');
$ProductsLocale = new QUI\Locale();
$ProductsLocale->setCurrent('de');
QUI\ERP\Products\Handler\Products::setLocale($ProductsLocale);

if (!QUITests\ERP\Products\DatabaseEnvironment::usesCiDatabase()) {
    require_once __DIR__ . '/SqlitePlatform.php';
    require_once __DIR__ . '/SqliteTestEnvironment.php';
}

if (!class_exists(QUI\ERP\Order\AbstractOrder::class)) {
    if (!interface_exists(QUI\ERP\Order\OrderArticle::class)) {
        require_once __DIR__ . '/stubs/OrderArticle.php';
    }

    if (!interface_exists(QUI\ERP\Order\OrderDeliveryAddress::class)) {
        require_once __DIR__ . '/stubs/OrderDeliveryAddress.php';
    }

    require_once __DIR__ . '/stubs/AbstractOrder.php';
}

require_once __DIR__ . '/stubs/QUI/ERP/DemoData/DemoDataStubs.php';
require_once __DIR__ . '/Fixtures/TestUser.php';
require_once __DIR__ . '/Fixtures/RecordingCalc.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/ProjectTestHelper.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/IntegrationTestEnvironment.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Product/ProductTestHelper.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Product/ProductIntegrationTestCase.php';
require_once __DIR__ . '/integration/QUI/ERP/Products/Ajax/AjaxTestCase.php';

QUI\System\TestCleanup::register();

if (!QUITests\ERP\Products\DatabaseEnvironment::usesCiDatabase()) {
    QUITests\ERP\Products\SqliteTestEnvironment::activate();
}

QUITests\ERP\Products\Integration\Product\ProductTestHelper::registerCleanup();
