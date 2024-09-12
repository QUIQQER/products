<?php

/**
 * This file contains the manufacturer site type
 *
 * @var QUI\Projects\Project $Project
 * @var QUI\Projects\Site $Site
 * @var QUI\Interfaces\Template\EngineInterface $Engine
 * @var QUI\Template $Template
 **/

use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Controls\ManufacturerList\ManufacturerList;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Manufacturers;
use QUI\ERP\Products\Utils\Sortables;

$Request = QUI::getRequest();
$siteUrl = $Site->getLocation();
$requestUrl = $_REQUEST['_url'];
$ManufacturerUser = null;
$ProductList = false;

// Check if a special manufacturer URL was called
if ($siteUrl !== $requestUrl) {
    $urlInfo = \pathinfo($requestUrl);
    $manufacturerUsername = $urlInfo['basename'];

    // Check if manufacturer user exists
    try {
        $ManufacturerUser = QUI::getUsers()->getUserByName($manufacturerUsername);

        if (!Manufacturers::isManufacturer($ManufacturerUser->getUUID())) {
            $ManufacturerUser = null;
        }

        $searchParams = [
            'fields' => [
                Fields::FIELD_MANUFACTURER => $ManufacturerUser?->getName()
            ]
        ];

        // Determine default sorting
        $defaultSorting = $Site->getAttribute('quiqqer.products.settings.defaultSorting');

        if (!empty($defaultSorting)) {
            $defaultSorting = \explode(' ', $defaultSorting);
            $searchParams['sortOn'] = $defaultSorting[0];

            if (!empty($defaultSorting[1])) {
                $searchParams['sortBy'] = $defaultSorting[1];
            }
        }

        $ProductList = new ProductList([
            'searchParams' => $searchParams,
            'hideEmptyProductList' => true,
            'view' => $Site->getAttribute('quiqqer.products.settings.categoryDisplay'),
            'autoload' => 1,
            'autoloadAfter' => $Site->getAttribute('quiqqer.products.settings.autoloadAfter'),
            'productLoadNumber' => $Site->getAttribute('quiqqer.products.settings.productLoadNumber'),
        ]);

        // Assign sort fields
        $fields = Sortables::getSortableFieldsForSite($Site);

        foreach ($fields as $fieldId) {
            if (\strpos($fieldId, 'S') === 0) {
                $title = QUI::getLocale()->get('quiqqer/products', 'sortable.' . \mb_substr($fieldId, 1));

                $ProductList->addSort(
                    $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortASC'),
                    $fieldId . ' ASC'
                );

                $ProductList->addSort(
                    $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortDESC'),
                    $fieldId . ' DESC'
                );

                continue;
            }

            if (\strpos($fieldId, 'F') === 0) {
                try {
                    $fieldId = str_replace('F', '', $fieldId);
                    $Field = Fields::getField((int)$fieldId);
                    $title = $Field->getTitle();

                    $ProductList->addSort(
                        $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortASC'),
                        'F' . $fieldId . ' ASC'
                    );

                    $ProductList->addSort(
                        $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortDESC'),
                        'F' . $fieldId . ' DESC'
                    );
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }

        $Engine->assign('manufacturerTitle', Manufacturers::getManufacturerTitle($ManufacturerUser?->getUUID()));
    } catch (\Exception $Exception) {
        QUI\System\Log::writeDebugException($Exception);
    }
}

$Engine->assign([
    'url' => $Site->getUrlRewrittenWithHost(),
    'ProductList' => $ProductList,
    'ManufacturerUser' => $ManufacturerUser,
    'ManufacturerList' => new ManufacturerList()
]);
