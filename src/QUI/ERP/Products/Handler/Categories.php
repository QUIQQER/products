<?php

/**
 * This file contains QUI\ERP\Products\Products\Controller
 */

namespace QUI\ERP\Products\Handler;

use QUI;

use function get_class;
use function is_null;
use function is_object;

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
    private static array $list = [];

    /**
     * Clears the category cache
     *
     * @param bool|integer $categoryId - optional, Category-ID,
     *                                   if false => complete categories cache is cleared
     */
    public static function clearCache($categoryId = false)
    {
        if ($categoryId === false) {
            QUI\Cache\LongTermCache::clear('quiqqer/products/categories/');
        } else {
            QUI\Cache\LongTermCache::clear(self::getCacheName($categoryId));
        }

        try {
            QUI::getEvents()->fireEvent('onQuiqqerProductsCategoriesClearCache');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Returns the cache name of a category
     *
     * @param integer|string $categoryId
     * @return string
     */
    public static function getCacheName($categoryId): string
    {
        return Cache::getBasicCachePath() . 'categories/' . (int)$categoryId;
    }

    /**
     * Return the number of the children
     *
     * @param array $queryParams - query params (where, where_or)
     * @return integer
     */
    public function countCategories(array $queryParams = []): int
    {
        $query = [
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            'count' => [
                'select' => 'id',
                'as' => 'count'
            ]
        ];

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
        }

        try {
            $data = QUI::getDataBase()->fetch($query);
        } catch (QUI\DataBase\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return 0;
        }


        if (isset($data[0]) && isset($data[0]['count'])) {
            return (int)$data[0]['count'];
        }

        return 0;
    }

    /**
     * @return array
     */
    public static function getChildAttributes(): array
    {
        return [
            'id',
            'parentId',
            'fields'
        ];
    }

    /**
     * @param integer|string $id
     * @return QUI\ERP\Products\Interfaces\CategoryInterface
     *
     * @throws QUI\Exception
     */
    public static function getCategory($id)
    {
        $id = (int)$id;

        if (isset(self::$list[$id])) {
            return self::$list[$id];
        }

        if ($id === 0) {
            self::$list[$id] = new QUI\ERP\Products\Category\AllProducts();

            return self::$list[$id];
        }

        // Wenn der RAM zu voll wird, Objekte mal leeren
        if (QUI\Utils\System::memUsageToHigh()) {
            self::$list = [];
        }

        try {
            $categoryData = QUI\Cache\LongTermCache::get(self::getCacheName($id));
        } catch (QUI\Exception $Exception) {
            $data = QUI::getDataBase()->fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                'where' => [
                    'id' => $id
                ]
            ]);

            if (!isset($data[0])) {
                throw new QUI\Exception(
                    ['quiqqer/products', 'exception.category.not.found'],
                    404,
                    ['categoryId' => $id]
                );
            }

            $categoryData = $data[0];

            QUI\Cache\LongTermCache::set(self::getCacheName($id), $categoryData);
        }


        $Category = new QUI\ERP\Products\Category\Category($id, $categoryData);

        self::$list[$id] = $Category;

        return $Category;
    }

    /**
     * Return the main category
     * New products are created automatically in this category.
     *
     * @return QUI\ERP\Products\Interfaces\CategoryInterface
     * @throws QUI\Exception
     */
    public static function getMainCategory()
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $mainCategory = $Config->get('products', 'mainCategory');

        if (!$mainCategory) {
            return self::getCategory(0);
        }

        return self::getCategory($mainCategory);
    }

    /**
     * Checks if a category exists
     *
     * @param integer|string $categoryId - category id
     * @return bool
     * @throws QUI\Exception
     */
    public static function existsCategory($categoryId): bool
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
    public static function isCategory($Category): bool
    {
        if (!is_object($Category)) {
            return false;
        }

        if (get_class($Category) === QUI\ERP\Products\Category\Category::class) {
            return true;
        }

        if (get_class($Category) === QUI\ERP\Products\Category\AllProducts::class) {
            return true;
        }

        return false;
    }

    /**
     * Create a new category
     *
     * @param integer|null|string $parentId - optional, ID of the parent
     * @param string $title - optional, translation text for current language
     *
     * @return QUI\ERP\Products\Interfaces\CategoryInterface
     *
     * @throws \QUI\Exception
     * @throws \QUI\Permissions\Exception
     */
    public static function createCategory($parentId = null, string $title = '')
    {
        QUI\Permissions\Permission::checkPermission('category.create');

        if (is_null($parentId)) {
            $parentId = 0;
        }

        $parentId = (int)$parentId;

        $result = QUI::getDataBase()->fetch([
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            'limit' => 1
        ]);

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.category.create', [
                'title' => $title
            ])
        );

        if (empty($result)) {
            QUI::getDataBase()->insert(
                QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                [
                    'parentId' => $parentId,
                    'id' => 1
                ]
            );
        } else {
            QUI::getDataBase()->insert(
                QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                [
                    'parentId' => $parentId
                ]
            );
        }


        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        // translation - title
        try {
            $current = QUI::getLocale()->getCurrent();

            $languageData = [
                'datatype' => 'js,php',
                'package' => 'quiqqer/products'
            ];

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

        try {
            $languageData = [
                'datatype' => 'js,php',
                'package' => 'quiqqer/products'
            ];

            foreach (QUI::availableLanguages() as $lang) {
                $languageData[$lang] = ' ';
                $languageData[$lang . '_edit'] = ' ';
            }

            QUI\Translator::addUserVar(
                'quiqqer/products',
                'products.category.' . $newId . '.description',
                $languageData
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());

            QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }

        $Category = self::getCategory($newId);

        QUI\ERP\Products\Handler\Categories::clearCache($parentId);
        QUI::getEvents()->fireEvent('onQuiqqerProductsCategoryCreate', [$Category]);

        return $Category;
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
     * @return QUI\ERP\Products\Interfaces\CategoryInterface[]
     */
    public static function getCategories(array $queryParams = []): array
    {
        $ids = self::getCategoryIds($queryParams);
        $result = [];

        foreach ($ids as $id) {
            try {
                $result[] = self::getCategory($id);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $result;
    }

    /**
     *
     * @param array $queryParams
     * @return array
     */
    public static function getCategoryIds(array $queryParams = []): array
    {
        $query = [
            'select' => 'id',
            'from' => QUI\ERP\Products\Utils\Tables::getCategoryTableName()
        ];

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

        $result = [];

        try {
            $data = QUI::getDataBase()->fetch($query);
        } catch (QUI\DataBase\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }


        foreach ($data as $entry) {
            $result[] = $entry['id'];
        }

        return $result;
    }

    /**
     * @param integer|string $id
     * @throws QUI\Exception
     */
    public static function deleteCategory($id)
    {
        self::getCategory($id)->delete();
    }
}
