<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

putenv("QUIQQER_OTHER_AUTOLOADERS=KEEP");

require_once __DIR__ . '/../../../../bootstrap.php';

if (!class_exists(\QUI\ERP\Order\AbstractOrder::class)) {
    require_once __DIR__ . '/stubs/OrderArticle.php';
    require_once __DIR__ . '/stubs/OrderDeliveryAddress.php';
    require_once __DIR__ . '/stubs/AbstractOrder.php';
}

require_once __DIR__ . '/stubs/QUI/ERP/DemoData/DemoDataStubs.php';
