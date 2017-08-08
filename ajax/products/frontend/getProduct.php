<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getProduct
 */

use QUI\ERP\Products\Product\Product;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;

/**
 * Return the product html
 *
 * @param string $productId - ID of a product
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getProduct',
    function ($productId, $project, $siteId) {
        $Project  = null;
        $Site     = null;
        $Template = null;
        $title    = '';

        try {
            $Project = QUI\Projects\Manager::decode($project);
            $Site    = $Project->get($siteId);

            $Template = QUI::getTemplateManager();
            $Template->setAttribute('Project', $Project);
            $Template->setAttribute('Site', $Site);

            $title = $Template->getTitle();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());
        }

        try {
            $Product = new Product($productId);
            $Control = new ProductControl(array(
                'Product' => $Product
            ));

            $control = $Control->create();

            if (empty($title)) {
                $title = $Product->getTitle();
            }

            return array(
                'css'   => QUI\Control\Manager::getCSS(),
                'html'  => $control,
                'title' => $title
            );
        } catch (QUI\Exception $Exception) {
        }

        return '';
    },
    array('productId', 'project', 'siteId')
);
