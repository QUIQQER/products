<?php

/**
 * This file contains QUI\ERP\Products\Utils\Search
 */

namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class Search
 */
class Search
{
    /**
     * @return array
     */
    public static function getSearchParameterFromRequest()
    {
        $search = QUI::getRequest()->get('search');
        $fields = QUI::getRequest()->get('f');
        $tags   = QUI::getRequest()->get('t');
        $sortBy = QUI::getRequest()->get('sortBy');
        $sortOn = QUI::getRequest()->get('sortOn');

        $categories = QUI::getRequest()->get('c');

        $Site           = QUI::getRewrite()->getSite();
        $defaultSorting = $Site->getAttribute('quiqqer.products.settings.defaultSorting');

        if ($categories) {
            $categories = explode(',', $categories);
        }

        if (!is_array($categories)) {
            $categories = array();
        }

        // look for default site settings
        if (!$sortOn && !empty($defaultSorting)) {
            $sorting = explode(' ', $defaultSorting);
            $sortOn  = $sorting[0];
        }

        if (!$sortBy && !empty($defaultSorting)) {
            $sorting = explode(' ', $defaultSorting);

            if (isset($sorting[1])) {
                switch ($sorting[1]) {
                    case 'DESC':
                    case 'ASC':
                        $sortBy = $sorting[1];
                }
            }
        }

        $searchParams = array_filter(array(
            'freetext' => $search,
            'fields'   => $fields,
            'tags'     => $tags,
            'sortBy'   => $sortBy,
            'sortOn'   => $sortOn,
        ));

        if (!empty($categories)) {
            $searchParams['categories'] = $categories;
        }

        if (isset($searchParams['fields'])) {
            $searchParams['fields'] = json_decode($searchParams['fields'], true);

            if (is_null($searchParams['fields'])) {
                unset($searchParams['fields']);
            }
        }

        if (isset($searchParams['tags'])) {
            $searchParams['tags'] = explode(',', $searchParams['tags']);
        }

        return $searchParams;
    }

    /**
     * @return mixed|string
     */
    public static function getViewParameterFromRequest()
    {
        if (QUI::getSession()->get('productView')) {
            return QUI::getSession()->get('productView');
        }

        if (QUI::getRequest()->get('v')) {
            return QUI::getRequest()->get('v');
        }

        return '';
    }

    /**
     * Return the default frontend fields
     * @return array
     */
    public static function getDefaultFrontendFields()
    {
        $Package    = QUI::getPackage('quiqqer/products');
        $defaultIds = $Package->getConfig()->get('search', 'frontend');
        $fields     = array();

        if ($defaultIds) {
            $defaultIds = explode(',', $defaultIds);

            foreach ($defaultIds as $fieldId) {
                try {
                    $fields[] = QUI\ERP\Products\Handler\Fields::getField($fieldId);
                } catch (QUI\Exception $Exception) {
                }
            }
        }

        return $fields;
    }
}
