<?php

/**
 * This file contains QUI\ERP\Products\Products\Controller
 */
namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Categories
 * @package QUI\ERP\Products\Handler
 */
class Categories
{
    /**
     * List of internal categories
     * @var array
     */
    private static $list = array();

    /**
     * @param integer $id
     * @return QUI\ERP\Products\Category\Category
     */
    public function getCategory($id)
    {
        if (isset(self::$list[$id])) {
            return self::$list[$id];
        }

        $Product         = new QUI\ERP\Products\Category\Category($id);
        self::$list[$id] = $Product;

        return $Product;
    }

    /**
     * Create a new category
     *
     * @param integer $parentId - optional, ID of the parent
     * @return QUI\ERP\Products\Product\Product
     */
    public static function createCategory($parentId)
    {
        QUI\Rights\Permission::checkPermission('category.create');

        if (!$parentId) {
            $parentId = 0;
        }

        QUI::getDataBase()->insert(
            QUI\ERP\Products\Tables::getCategoryTableName(),
            array(
                'parentId' => $parentId
            )
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        return self::getCategory($newId);
    }

    /**
     * Return a list of categories
     * if $queryParams is empty, all fields are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array
     */
    public static function getCategories($queryParams = array())
    {
        $query['from'] = QUI\ERP\Products\Tables::getCategoryTableName();

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        if (isset($queryParams['order'])) {
            $query['order'] = $queryParams['order'];
        }

        foreach ($data as $entry) {
            try {
                $result[] = self::getCategory($entry['id']);
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * @param integer $id
     */
    public static function deleteProduct($id)
    {
        self::getCategory($id)->delete();
    }
}
