<?php

/**
 * This file contains QUI\ERP\Products\Category\AllProducts
 */

namespace QUI\ERP\Products\Category;

use QUI;
use QUI\ERP\Products\Handler\Products;

/**
 * Class AllProducts
 * This category is to access all products
 * Its a virtual category
 *
 * @package QUI\ERP\Products\Category
 */
class AllProducts extends Category
{
    /**
     * AllProducts constructor.
     * @param int $categoryId - can't be used, id is always 0
     * @param array $data
     */
    public function __construct($categoryId = 0, array $data = [])
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
    public function getProducts($params = [])
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
    public function getProductIds($params = [])
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
    public function countProducts($params = [])
    {
        if (!\is_array($params)) {
            $params = [];
        }

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
     */
    public function getFields()
    {
        return QUI\ERP\Products\Search\Utils::getDefaultFrontendFields();
    }

    /**
     * The save method of the AllProducts do nothing
     * The AllProducts Category can't be edited
     *
     * @param bool|QUI\Interfaces\Users\User $User
     */
    public function save($User = false)
    {
        // do nothing
    }

    /**
     * The delete method of the AllProducts do nothing
     * The AllProducts Category can't be edited
     *
     * @param bool|QUI\Interfaces\Users\User $User
     */
    public function delete($User = false)
    {
        // do nothing
    }

    /**
     * The setParentId method of the AllProducts do nothing
     * The AllProducts Category can't be edited
     *
     * @param int $parentId
     */
    public function setParentId($parentId)
    {
        // do nothing
    }
}
