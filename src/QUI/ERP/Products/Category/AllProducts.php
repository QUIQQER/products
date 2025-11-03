<?php

/**
 * This file contains QUI\ERP\Products\Category\AllProducts
 */

namespace QUI\ERP\Products\Category;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\Exception;

use function is_array;

/**
 * Class AllProducts
 * This category is to access all products
 * It's a virtual category
 *
 * @package QUI\ERP\Products\Category
 */
class AllProducts extends Category
{
    /**
     * AllProducts constructor.
     *
     * @param int $categoryId - can't be used, id is always 0
     * @param array $data
     */
    public function __construct(protected int $categoryId = 0, array $data = [])
    {
        $data['parentId'] = 0;

        parent::__construct(0, $data);
    }

    /**
     * Return all products
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['limit']
     *                              $queryParams['order']
     *                              $queryParams['debug']
     * @return array
     */
    public function getProducts(array $params = []): array
    {
        $query = [
            'limit' => 20
        ];

        if (isset($params['where'])) {
            $query['where'] = $params['where'];
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['debug'])) {
            $query['debug'] = $params['debug'];
        }

        return Products::getProducts($query);
    }

    /**
     * Return all product ids
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['limit']
     *                              $queryParams['order']
     *                              $queryParams['debug']
     * @return array
     */
    public function getProductIds(array $params = []): array
    {
        $query = [];

        if (isset($params['where'])) {
            $query['where'] = $params['where'];
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        if (isset($params['order'])) {
            $query['order'] = $params['order'];
        }

        if (isset($params['debug'])) {
            $query['debug'] = $params['debug'];
        }

        return Products::getProductIds($query);
    }

    /**
     * Return the number of all products
     *
     * @param array $params - query parameter
     *                              $queryParams['where']
     *                              $queryParams['debug']
     * @return integer
     */
    public function countProducts(array $params = []): int
    {
        $query = [];

        if (isset($params['where'])) {
            $query['where'] = $params['where'];
        }

        if (isset($params['debug'])) {
            $query['debug'] = $params['debug'];
        }

        return Products::countProducts($query);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFields(): array
    {
        return QUI\ERP\Products\Search\Utils::getDefaultFrontendFields();
    }

    /**
     * The save method of the AllProducts do nothing
     * The AllProducts Category can't be edited
     *
     * @param bool|QUI\Interfaces\Users\User $User
     */
    public function save($User = false): void
    {
        // do nothing
    }

    /**
     * The delete method of the AllProducts do nothing
     * The AllProducts Category can't be edited
     *
     * @param bool|QUI\Interfaces\Users\User $User
     */
    public function delete($User = false): void
    {
        // do nothing
    }

    /**
     * The setParentId method of the AllProducts do nothing
     * The AllProducts Category can't be edited
     *
     * @param int $parentId
     */
    public function setParentId(int $parentId): void
    {
        // do nothing
    }
}
