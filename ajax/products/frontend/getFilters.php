<?php

/**
 * This file contains package_quiqqer_products_ajax_products_frontend_getFilters
 */

/**
 * Return the fields from a product list
 *
 * @param string $project - Project data
 * @param string|int $id - Site ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_products_frontend_getFilters',
    function ($project, $siteId) {
        try {
            $Project = QUI::getProjectManager()->decode($project);
            $Site    = $Project->get($siteId);

            $Site->load();

            $ProductList = new QUI\ERP\Products\Controls\Category\ProductList([
                'Site'                 => $Site,
                'categoryId'           => $Site->getAttribute('quiqqer.products.settings.categoryId'),
                'hideEmptyProductList' => true,
                'categoryStartNumber'  => $Site->getAttribute('quiqqer.products.settings.categoryStartNumber'),
                'categoryView'         => $Site->getAttribute('quiqqer.products.settings.categoryDisplay')
            ]);

            // category menu
            $searchParentCategorySite = function () use ($Site) {
                $Parent = true;

                while ($Parent) {
                    if ($Site->getParent()
                        && $Site->getParent()->getAttribute('type') != 'quiqqer/products:types/category'
                    ) {
                        return $Site;
                    }

                    $Site = $Site->getParent();

                    if (!$Site) {
                        break;
                    }
                }

                return $Site;
            };

            $result = '';
            $Parent = $searchParentCategorySite();

            $CategoryMenu = new QUI\ERP\Products\Controls\Category\Menu([
                'Site'              => $Parent,
                'disableCheckboxes' => false,
                'breadcrumb'        => true
            ]);

            $Output = new QUI\Output();

            if ($CategoryMenu->hasCategoryCheckBox($Site)) {
                $result .= '<header>';
                $result .= '<h2>'.QUI::getLocale()->get('quiqqer/products', 'type.category.categoryTitle').'</h2>';
                $result .= '</header>';
                $result .= $CategoryMenu->create();
            }

            if ($Site->getAttribute('quiqqer.products.settings.showFreeTextSearch')) {
                $placeholder = QUI::getLocale()->get("quiqqer/products", "control.search.placeholder");

                $result .= '<header>';
                $result .= '<h2>'.QUI::getLocale()->get('quiqqer/products', 'type.category.freetextTitle').'</h2>';
                $result .= '</header>';
                $result .= '<label class="quiqqer-products-category-freetextSearch">';
                $result .= '<input type="search" name="search"';
                $result .= '    placeholder="'.$placeholder.'" />';
                $result .= '</label>';
            }

            if (\count($ProductList->getFilter())) {
                if ($result !== '') {
                    $result .= '<header>';
                    $result .= '<h2>';
                    $result .= QUI::getLocale()->get('quiqqer/products', 'type.category.filterTitle');
                    $result .= '</h2>';
                    $result .= '</header>';
                }

                $result .= $ProductList->createFilter();
            }

            QUI::getMessagesHandler()->clear();

            return $Output->parse($result);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
        }

        return '';
    },
    ['project', 'siteId']
);
