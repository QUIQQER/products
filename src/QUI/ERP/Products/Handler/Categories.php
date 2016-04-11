<?php

/**
 * This file contains QUI\ERP\Products\Products\Controller
 */
namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Categories
 *
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
     * Clears the category cache
     *
     * @param bool|integer $categoryId - optional, Category-ID,
     *                                   if false => complete categories cache is cleared
     */
    public static function clearCache($categoryId = false)
    {
        if ($categoryId === false) {
            QUI\Cache\Manager::clear('quiqqer/products/categories/');
        } else {
            QUI\Cache\Manager::clear(self::getCacheName($categoryId));
        }
    }

    /**
     * Returns the cache name of a category
     *
     * @param integer $categoryId
     * @return string
     */
    public static function getCacheName($categoryId)
    {
        return 'quiqqer/products/categories/' . (int)$categoryId;
    }

    /**
     * Return the number of the children
     *
     * @param array $queryParams - query params (where, where_or)
     * @return integer
     */
    public function countCategories($queryParams = array())
    {
        $query = array(
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            'count' => array(
                'select' => 'id',
                'as' => 'count'
            )
        );

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
        }

        $data = QUI::getDataBase()->fetch($query);

        if (isset($data[0]) && isset($data[0]['count'])) {
            return (int)$data[0]['count'];
        }

        return 0;
    }

    /**
     * @return array
     */
    public static function getChildAttributes()
    {
        return array(
            'id',
            'parentId',
            'fields'
        );
    }

    /**
     * @param integer $id
     * @return QUI\ERP\Products\Category\Category
     *
     * @throws QUI\Exception
     */
    public static function getCategory($id)
    {
        if (isset(self::$list[$id])) {
            return self::$list[$id];
        }

        $categoryData = array();

        if ($id !== 0) {
            try {
                $categoryData = QUI\Cache\Manager::get(self::getCacheName($id));

            } catch (QUI\Exception $Eception) {
                $data = QUI::getDataBase()->fetch(array(
                    'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                    'where' => array(
                        'id' => $id
                    )
                ));

                if (!isset($data[0])) {
                    throw new QUI\Exception(
                        array(
                            'quiqqer/products',
                            'exception.category.not.found'
                        ),
                        404,
                        array(
                            'categoryId' => $id
                        )
                    );
                }

                $categoryData = $data[0];

                QUI\Cache\Manager::set(self::getCacheName($id), $categoryData);
            }
        }

        $Product         = new QUI\ERP\Products\Category\Category($id, $categoryData);
        self::$list[$id] = $Product;

        return $Product;
    }

    /**
     * Checks if a category exists
     *
     * @param integer $categoryId - category id
     * @return bool
     * @throws QUI\Exception
     */
    public static function existsCategory($categoryId)
    {
        try {
            self::getCategory($categoryId);
        } catch (QUI\Exception $Exception) {
            if ($Exception->getCode() === 404) {
                return false;
            }

            throw $Exception;
        }

        return true;
    }

    /**
     * Is the Object a category?
     *
     * @param mixed $Category
     * @return boolean
     */
    public static function isCategory($Category)
    {
        if (!is_object($Category)) {
            return false;
        }

        if (get_class($Category) === 'QUI\ERP\Products\Category\Category') {
            return true;
        }

        return false;
    }

    /**
     * Create a new category
     *
     * @param integer $parentId - optional, ID of the parent
     * @param string $title - optional, translation text for current language
     *
     * @return QUI\ERP\Products\Product\Product
     */
    public static function createCategory($parentId = null, $title = '')
    {
        QUI\Rights\Permission::checkPermission('category.create');

        if (is_null($parentId)) {
            $parentId = 0;
        }

        $result = QUI::getDataBase()->fetch(array(
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            'limit' => 1
        ));

        if (empty($result)) {
            QUI::getDataBase()->insert(
                QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                array(
                    'parentId' => $parentId,
                    'id' => 1
                )
            );
        } else {
            QUI::getDataBase()->insert(
                QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                array(
                    'parentId' => $parentId
                )
            );
        }


        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        // translation - title
        try {
            $current      = QUI::getLocale()->getCurrent();
            $languageData = array(
                'datatype' => 'js,php'
            );

            if (!empty($title)) {
                $languageData[$current] = $title;
            }

            QUI\Translator::addUserVar(
                'quiqqer/products',
                'products.category.' . $newId . '.title',
                $languageData
            );

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());

            QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }

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
        $query = array(
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName()
        );

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

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

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
    public static function deleteCategory($id)
    {
        self::getCategory($id)->delete();
    }
}
