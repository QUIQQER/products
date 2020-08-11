<?php

use QUI\ERP\Products\Handler\Manufacturers;
use QUI\ERP\Products\Controls\ManufacturerList\ManufacturerList;
use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Handler\Fields;

$Request          = QUI::getRequest();
$siteUrl          = $Site->getLocation();
$requestUrl       = $_REQUEST['_url'];
$ManufacturerUser = false;
$ProductList      = false;

// Check if a special manufacturer URL was called
if ($siteUrl !== $requestUrl) {
    $urlInfo              = \pathinfo($requestUrl);
    $manufacturerUsername = $urlInfo['basename'];

    // Check if manufacturer user exists
    try {
        $ManufacturerUser = QUI::getUsers()->getUserByName($manufacturerUsername);

        if (!Manufacturers::isManufacturer($ManufacturerUser->getId())) {
            $ManufacturerUser = false;
        }

        $ProductList = new ProductList([
            'searchParams'         => [
                'fields' => [
                    Fields::FIELD_MANUFACTURER => $ManufacturerUser->getName()
                ]
            ],
            'hideEmptyProductList' => true,
            'view'                 => $Site->getAttribute('quiqqer.products.settings.categoryDisplay'),
            'autoload'             => 1,
            'autoloadAfter'        => $Site->getAttribute('quiqqer.products.settings.autoloadAfter'),
            'productLoadNumber'    => $Site->getAttribute('quiqqer.products.settings.productLoadNumber'),
        ]);

        $Engine->assign('manufacturerTitle', Manufacturers::getManufacturerTitle($ManufacturerUser->getId()));
    } catch (\Exception $Exception) {
        QUI\System\Log::writeDebugException($Exception);
    }
}

$Engine->assign([
    'url'              => $Site->getUrlRewrittenWithHost(),
    'ProductList'      => $ProductList,
    'ManufacturerUser' => $ManufacturerUser,
    'ManufacturerList' => new ManufacturerList()
]);
