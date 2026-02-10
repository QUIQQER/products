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
