<?php

/**
 * This file contains QUI\ERP\Products\DesktopSearch
 */
namespace QUI\ERP\Products;

use QUI;

/**
 * Class DesktopSearch
 *
 * @package QUI\Products
 */
class DesktopSearch implements QUI\Workspace\Search\ProviderInterface
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
        return array(
            'searchdata' => json_encode(array(
                'require' => 'package/quiqqer/products/bin/controls/products/Product',
                'params'  => array(
                    'productId' => (int)$id
                )
            ))
        );
    }

    /**
     * Execute a search
     *
     * @param string $search
     * @param array $params
     * @return array
     */
    public function search($search, $params = array())
    {
        $result = array();
        $Search = QUI\ERP\Products\Handler\Search::getBackendSearch();

        try {
            $products = $Search->search(array(
                'freetext' => $search,
                'limit'    => 10
            ));
        } catch (QUI\Permissions\Exception $Exception) {
            return array();
        }

        foreach ($products as $productId) {
            try {
                $Product = QUI\ERP\Products\Handler\Products::getProduct($productId);

                $result[] = array(
                    'id'          => (int)$productId,
                    'title'       => $Product->getTitle(),
                    'description' => $Product->getDescription(),
                    'icon'        => 'fa fa-shopping-bag',
                    'searchtype'  => self::TYPE
                );
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $result;
    }
}
