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
        }

        $Product         = new QUI\ERP\Products\Category\Category($id, $categoryData);
        self::$list[$id] = $Product;

        return $Product;
    }

    /**
     * Create a new category
     *
     * @param integer $parentId - optional, ID of the parent
     * @param string $title - optional, translation text for current language
     *
     * @return QUI\ERP\Products\Product\Product
     */
    public static function createCategory($parentId, $title = '')
    {
        QUI\Rights\Permission::checkPermission('category.create');

        if (!$parentId) {
            $parentId = 0;
        }

        QUI::getDataBase()->insert(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array(
                'parentId' => $parentId
            )
        );

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
        $query['from'] = QUI\ERP\Products\Utils\Tables::getCategoryTableName();

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
