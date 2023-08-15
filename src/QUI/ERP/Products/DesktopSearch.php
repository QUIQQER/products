<?php

/**
 * This file contains QUI\ERP\Products\DesktopSearch
 */

namespace QUI\ERP\Products;

use QUI;
use QUI\BackendSearch\ProviderInterface;

/**
 * Class DesktopSearch
 *
 * @package QUI\Products
 */
class DesktopSearch implements ProviderInterface
{
    const TYPE = 'products';

    /**
     * @inheritdoc
     */
    public function buildCache()
    {
        // placeholder, not needed
    }

    /**
     * @param int $id
     * @inheritdoc
     */
    public function getEntry($id)
    {
        return [
            'searchdata' => \json_encode([
                'require' => 'package/quiqqer/products/bin/controls/products/Product',
                'params' => [
                    'productId' => (int)$id
                ]
            ])
        ];
    }

    /**
     * Execute a search
     *
     * @param string $search
     * @param array $params
     * @return array
     */
    public function search($search, $params = [])
    {
        if (
            isset($params['filterGroups'])
            && !\in_array(self::TYPE, $params['filterGroups'])
        ) {
            return [];
        }

        $result = [];
        $Search = QUI\ERP\Products\Handler\Search::getBackendSearch();

        try {
            $products = $Search->search([
                'freetext' => $search,
                'limit' => 10
            ]);
        } catch (QUI\Permissions\Exception $Exception) {
            return [];
        }

        $groupLabel = QUI::getLocale()->get(
            'quiqqer/products',
            'search.group.products.label'
        );

        foreach ($products as $productId) {
            try {
                $Product = QUI\ERP\Products\Handler\Products::getProduct($productId);

                $result[] = [
                    'id' => (int)$productId,
                    'title' => $Product->getTitle(),
                    'description' => $Product->getDescription(),
                    'icon' => 'fa fa-shopping-bag',
                    'group' => self::TYPE,
                    'groupLabel' => $groupLabel
                ];
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $result;
    }

    /**
     * Get all available search groups of this provider.
     * Search results can be filtered by these search groups.
     *
     * @return array
     */
    public function getFilterGroups()
    {
        return [
            [
                'group' => self::TYPE,
                'label' => [
                    'quiqqer/products',
                    'search.group.products.label'
                ]
            ]
        ];
    }
}
