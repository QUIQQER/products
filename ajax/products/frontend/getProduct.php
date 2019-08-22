<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProduct
 */

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;

/**
 * Return the product html
 *
 * @param string $productId - ID of a product
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getProduct',
    function ($productId, $project, $siteId) {
        $Project = QUI\Projects\Manager::decode($project);

        $cache = 'quiqqer-products/control/product/';
        $cache .= $Project->getName().'/';
        $cache .= $Project->getLang().'/';
        $cache .= $siteId.'/';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $Site     = null;
        $Template = null;
        $Locale   = QUI::getLocale();
        $title    = '';

        try {
            $Product = Products::getNewProductInstance($productId);
        } catch (QUI\Exception $Exception) {
            return '';
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

        try {
            $Control = new ProductControl([
                'Product' => $Product
            ]);

            $control = $Control->create();

            if (empty($title)) {
                $title = $Product->getTitle();
            }

            $result = [
                'css'   => QUI\Control\Manager::getCSS(),
                'html'  => QUI\Output::getInstance()->parse($control),
                'title' => $title
            ];

            QUI\Cache\Manager::set($cache, $result);

            return $result;
        } catch (QUI\Exception $Exception) {
        }

        return '';
    },
    ['productId', 'project', 'siteId']
);
