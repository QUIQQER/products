<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProduct
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUI\ERP\Products\Utils\Products as ProductUtils;

/**
 * Return the product html
 *
 * @param string $productId - ID of a product
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getProduct',
    function ($productId, $project, $siteId) {
        $cacheName = 'quiqqer/products/frontendCache/'.\md5(\serialize([$productId, $project, $siteId]));

        // caching only if prices are hidden
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            try {
                return QUI\Cache\Manager::get($cacheName);
            } catch (QUI\Exception $Exception) {
            }
        }

        $Project  = QUI\Projects\Manager::decode($project);
        $Site     = null;
        $Template = null;
        $Locale   = QUI::getLocale();
        $title    = '';

        try {
            $Product = Products::getNewProductInstance($productId);
        } catch (QUI\Exception $Exception) {
            return '';
        }

        $availableHashes = [];

        if (\method_exists($Product, 'availableActiveFieldHashes')) {
            $availableHashes = $Product->availableActiveFieldHashes();
        }

        try {
            $Site = $Project->get($siteId);
            $Site->load();

            $Template = QUI::getTemplateManager();
            $Template->setAttribute('Project', $Project);
            $Template->setAttribute('Site', $Site);

            $Site->setAttribute('meta.seotitle', $Product->getTitle($Locale));
            $Site->setAttribute('meta.description', $Product->getDescription($Locale));

            $title = $Template->getTitle();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());
        }

        $Control = new ProductControl([
            'Product' => $Product
        ]);

        $control = $Control->create();

        if (empty($title)) {
            $title = $Product->getTitle();
        }

        $result = [
            'css'             => QUI\Control\Manager::getCSS(),
            'html'            => QUI\Output::getInstance()->parse($control),
            'title'           => $title,
            'fieldHashes'     => ProductUtils::getJsFieldHashArray($Product),
            'availableHashes' => \array_flip($availableHashes)
        ];

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            QUI\Cache\Manager::set($cacheName, $result);
        }

        return $result;
    },
    ['productId', 'project', 'siteId']
);
