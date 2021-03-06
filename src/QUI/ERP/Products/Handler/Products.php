<?php

/**
 * This file contains QUI\ERP\Products\Handler\Products
 */

namespace QUI\ERP\Products\Handler;

use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;

use QUI\Projects\Media\Utils as FolderUtils;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\Projects\Media\Utils;

/**
 * Class Products
 * get and add new products
 *
 * @package QUI\ERP\Products\Handler
 */
class Products
{
    /**
     * This global flag determines if Product data is written to the database when
     * the Product->save() or Product->userSave() method is called.
     *
     * The intention is to disable expensive database queries in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * This also includes updating the product cache table that is used in the product search.
     *
     * @var bool
     */
    public static $writeProductDataToDb = true;

    /**
     * This global flag determines if the Product search cache is actually written when Product->updateCache()
     * is called.
     *
     * The intention is to disable expensive Product data collection and database queries in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * @var bool
     */
    public static $updateProductSearchCache = true;

    /**
     * This global flag determines if Product specific events are fired when a Product is saved.
     *
     * The intention is to disable expensive event handler operations in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * @var bool
     */
    public static $fireEventsOnProductSave = true;

    /**
     * This global flag determines if UniqueProduct data is cached in the RAM during runtime
     *
     * The intention is to disable expensive caching operations in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * @var bool
     */
    public static $useRuntimeCacheForUniqueProducts = true;

    /**
     * This enables the caching flag, equal if quiqqer is in frontend or backend
     *
     * @var bool
     */
    public static $createFrontendCache = false;

    /**
     * Product permission using?
     * @var null|boolean
     */
    private static $usePermissions = null;

    /**
     * List of internal products
     * @var array
     */
    private static $list = [];

    /**
     * List of internal products, via its own urls
     *
     * @var array
     */
    private static $productUrls = [];

    /**
     * Global Product Locale
     *
     * @var null
     */
    private static $Locale = null;

    /**
     * Return the main media folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public static function getParentMediaFolder()
    {
        $Config    = QUI::getPackage('quiqqer/products')->getConfig();
        $folderUrl = $Config->get('products', 'folder');

        if (empty($folderUrl)) {
            throw new QUI\Exception([
                'quiqqer/products',
                'exception.products.media.folder.missing'
            ]);
        }

        try {
            $Folder = FolderUtils::getMediaItemByUrl($folderUrl);

            /* @var $Folder QUI\Projects\Media\Folder */
            if (FolderUtils::isFolder($Folder)) {
                return $Folder;
            }
        } catch (QUI\Exception $Exception) {
        }

        throw new QUI\Exception([
            'quiqqer/products',
            'exception.products.media.folder.missing'
        ]);
    }

    /**
     * Return a specific product
     *
     * @param integer $pid - Product-ID
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public static function getProduct($pid)
    {
        if (!\is_numeric($pid)) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found',
                    ['productId' => $pid]
                ],
                404,
                ['id' => $pid]
            );
        }

        if (isset(self::$list[$pid])) {
            return self::$list[$pid];
        }

        // Wenn der RAM zu voll wird, Objekte mal leeren
        if (QUI\Utils\System::memUsageToHigh()) {
            self::$list = [];
        }

        // check if serialize product exists
        $cachePath = Cache::getProductCachePath($pid).'/db-data';

        //if (QUI::isFrontend()) { // -> mor wollte dies raus haben
        try {
            $product          = QUI\Cache\LongTermCache::get($cachePath);
            self::$list[$pid] = self::getProductByDataResult($pid, $product);

            return self::$list[$pid];
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
        //        }

        $Product          = self::getNewProductInstance($pid);
        self::$list[$pid] = $Product;

        return $Product;
    }

    /**
     * This clean the instance cache for the product manager
     * use this with caution
     */
    public static function cleanProductInstanceMemCache()
    {
        self::$list = [];
    }

    /**
     * Return a product by its own url
     *
     * @param string $url
     * @param int $category
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public static function getProductByUrl($url, $category)
    {
        $field = 'F'.QUI\ERP\Products\Handler\Fields::FIELD_URL;

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => [$field, 'category', 'id'],
                'from'   => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                'where'  => [
                    $field     => $url,
                    'category' => [
                        'type'  => '%LIKE%',
                        'value' => ','.$category.','
                    ]
                ],
                'limit'  => 1
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        if (!isset($result) || !isset($result[0])) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found.unknown'
                ],
                404,
                [
                    'url'      => $url,
                    'category' => $category
                ]
            );
        }

        return self::getNewProductInstance($result[0]['id']);
    }

    /**
     * Return a new product instance
     * this function does not look into the instance cache
     *
     * @param $pid
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public static function getNewProductInstance($pid)
    {
        if (!\is_numeric($pid)) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found',
                    ['productId' => $pid]
                ],
                404,
                ['id' => $pid]
            );
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'from'  => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where' => [
                    'id' => $pid
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception $Exception) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found',
                    ['productId' => $pid]
                ],
                404,
                ['id' => $pid]
            );
        }


        if (!isset($result[0])) {
            try {
                // if not exists, so we cleanup the cache table, too
                QUI::getDataBase()->delete(
                    QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                    ['id' => $pid]
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }

            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found',
                    ['productId' => $pid]
                ],
                404,
                ['id' => $pid]
            );
        }

        $productData = $result[0];

        if (QUI::isFrontend() || self::$createFrontendCache) {
            $cachePath = Cache::getProductCachePath($pid).'/db-data';

            try {
                QUI\Cache\LongTermCache::get($cachePath);
            } catch (QUI\Exception $Exception) {
                QUI\Cache\LongTermCache::set($cachePath, $productData);
            }
        }

        return self::getProductByDataResult($pid, $productData);
    }

    /**
     * @param $pid
     * @param $result
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * // @todo interface check
     */
    protected static function getProductByDataResult($pid, $result)
    {
        $type = $result['type'];

        if (empty($type)) {
            $type = QUI\ERP\Products\Product\Types\Product::class;
        }

        return new $type($pid, $result);
    }

    /**
     * Exists a product?
     *
     * @param integer $pid - Product-ID
     * @return boolean
     */
    public static function existsProduct($pid)
    {
        if (isset(self::$list[$pid])) {
            return true;
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'from'  => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where' => [
                    'id' => $pid
                ],
                'limit' => 1
            ]);
        } catch (\Exception $Exception) {
            return false;
        }

        return isset($result[0]);
    }

    /**
     * Get product by product no
     *
     * @param string $productNo - Product-No
     * @return QUI\ERP\Products\Product\Product
     * @throws QUI\Exception
     */
    public static function getProductByProductNo($productNo)
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => [
                    'id'
                ],
                'from'   => TablesUtils::getProductCacheTableName(),
                'where'  => [
                    'productNo' => $productNo
                ],
                'limit'  => 1
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());

            throw new QUI\Exception(
                ['quiqqer/products', 'exception.get.product.by.no.error'],
                $Exception->getCode(),
                ['productNo' => $productNo]
            );
        }

        if (empty($result) || !isset($result[0]['id'])) {
            throw new QUI\Exception(
                ['quiqqer/products', 'exception.product.no.not.found'],
                404,
                ['productNo' => $productNo]
            );
        }

        return self::getProduct($result[0]['id']);
    }

    /**
     * Create a new Product
     *
     * @param array $categories - list of category IDs or category Objects
     * @param array $fields - optional, list of fields (Field, Field, Field)
     * @param string $productType - optional, product type
     * @param integer|null $parent - optional, parent product
     *
     * @return QUI\ERP\Products\Product\Product|VariantChild|VariantParent
     *
     * @throws QUI\Exception
     */
    public static function createProduct(
        $categories = [],
        $fields = [],
        $productType = '',
        $parent = null
    ) {
        QUI\Permissions\Permission::checkPermission('product.create');

        // product type
        $type = QUI\ERP\Products\Product\Types\Product::class;

        if (!empty($productType) && $productType !== $type) {
            $productType  = \trim($productType, '\\');
            $ProductTypes = QUI\ERP\Products\Utils\ProductTypes::getInstance();

            if ($ProductTypes->exists($productType)) {
                $type = $productType;
            }
        }

        // categories
        $categoryIds = [];

        if (empty($categories)) {
            $categoryIds[] = Categories::getMainCategory();
        }

        foreach ($categories as $Category) {
            if (!\is_object($Category)) {
                try {
                    $Category      = Categories::getCategory($Category);
                    $categoryIds[] = $Category->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addWarning($Exception->getMessage());
                }

                continue;
            }

            if (Categories::isCategory($Category)) {
                /* @var $Category Category */
                $categoryIds[] = $Category->getId();
                continue;
            }

            throw new QUI\Exception([
                'quiqqer/products',
                'exception.products.no.category'
            ]);
        }

        if (!\count($categoryIds)) {
            throw new QUI\Exception([
                'quiqqer/products',
                'exception.products.no.category.given'
            ]);
        }

        // fields
        $fieldData = [];

        /* @var $Field Field|integer */
        foreach ($fields as $Field) {
            if (!\is_object($Field)) {
                try {
                    $Field = Fields::getField($Field);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addWarning($Exception->getMessage());
                    continue;
                }
            }

            $value = $Field->getValue();

            if ($Field->isRequired()) {
                if ($value === '') {
                    throw new QUI\Exception([
                        'quiqqer/products',
                        'exception.field.is.invalid',
                        [
                            'fieldId'    => $Field->getId(),
                            'fieldtitle' => $Field->getTitle()
                        ]
                    ]);
                }

                $Field->validate($Field->getValue());
            }

            $fieldData[] = $Field->toProductArray();
        }

        if ($parent !== null) {
            $parent = (int)$parent;
        }

        QUI::getDataBase()->insert(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            [
                'fieldData'  => \json_encode($fieldData),
                'categories' => ','.\implode(',', $categoryIds).',',
                'type'       => $type,
                'c_user'     => QUI::getUserBySession()->getId(),
                'c_date'     => date('Y-m-d H:i:s'),
                'parent'     => $parent
            ]
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.create', [
                'id' => $newId
            ]),
            '',
            [
                'fieldData'  => $fieldData,
                'categories' => ','.\implode(',', $categoryIds).','
            ]
        );


        $Product = self::getProduct($newId);

        if (!($Product instanceof VariantChild)) {
//            $Product->getField(QUI\ERP\Products\Handler\Fields::FIELD_FOLDER)->setValue('');
            $Product->createMediaFolder(); // the product is also saved in this method
        }

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCreate', [$Product]);

        return $Product;
    }

    /**
     * Copy a product
     *
     * @param integer $productId
     * @return QUI\ERP\Products\Product\Product
     *
     * @throws QUI\Exception
     */
    public static function copyProduct($productId)
    {
        $Product = self::getProduct($productId);

        $New = self::createProduct(
            $Product->getCategories(),
            $Product->getFields()
        );

        $New->setPermissions($Product->getPermissions());
        $New->setMainCategory($Product->getCategory());

        $folders = $New->getFieldsByType(Fields::TYPE_FOLDER);

        // @todo sub media folder kopieren wäre sinnvoller.
        // vorerst leer machen, so wird dann ein neuer ordner erstellt

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($folders as $Field) {
            $Field->setValue('');
        }

        // neuer media ordner erstellen
        $New->createMediaFolder(Fields::FIELD_FOLDER);


        // @todo titel setzen -> Kopie von

        $New->save();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCopy', [$New, $Product]);

        return $New;
    }

    /**
     * Return a list of products
     * if $queryParams is empty, all fields are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array
     */
    public static function getProducts($queryParams = [])
    {
        $result = [];
        $data   = self::getProductIds($queryParams);

        foreach ($data as $id) {
            try {
                $result[] = self::getProduct($id);
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * Return a list of product ids
     * if $queryParams is empty, all products are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array
     */
    public static function getProductIds($queryParams = [])
    {
        $query = [
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName()
        ];

        $allowedFields = [
            'id',
            'category',
            'categories',
            'fieldData',
            'active',
            'parent',
            'permissions',
            'e_date',
            'c_date',
            'orderCount',
        ];

        if (isset($queryParams['where']) &&
            QUI\Database\DB::isWhereValid($queryParams['where'], $allowedFields)
        ) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or']) &&
            QUI\Database\DB::isWhereValid($queryParams['where_or'], $allowedFields)
        ) {
            $query['where_or'] = $queryParams['where_or'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        if (isset($queryParams['order']) &&
            QUI\Database\DB::isOrderValid($queryParams['order'], $allowedFields)
        ) {
            $query['order'] = $queryParams['order'];
        }

        if (isset($queryParams['debug'])) {
            $query['debug'] = $queryParams['debug'];
        }

        $result = [];

        try {
            $data = QUI::getDataBase()->fetch($query);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        foreach ($data as $entry) {
            $result[] = $entry['id'];
        }

        return $result;
    }

    /**
     * @param integer $pid
     * @throws QUI\Exception
     */
    public static function deleteProduct($pid)
    {
        self::getProduct($pid)->delete();
    }

    /**
     * Return the number of the products
     * Count products
     *
     * @param array $queryParams - query params (where, where_or)
     * @return integer
     */
    public static function countProducts($queryParams = [])
    {
        $query = [
            'from'  => QUI\ERP\Products\Utils\Tables::getProductTableName(),
            'count' => [
                'select' => 'id',
                'as'     => 'count'
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
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return 0;
        }

        if (isset($data[0]) && isset($data[0]['count'])) {
            return (int)$data[0]['count'];
        }

        return 0;
    }

    /**
     * Cleanup all products
     */
    public static function cleanup()
    {
        // cache cleanup
        QUI\ERP\Products\Search\Cache::clear();
        Categories::clearCache();


        $ids = self::getProductIds();

        foreach ($ids as $id) {
            try {
                $Product = self::getProduct($id);
                $Product->save();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::write($Exception->getMessage(), QUI\System\Log::LEVEL_WARNING);
            }
        }

        // kategorien
        $categories = Categories::getCategories();

        /* @var $Category Category */
        foreach ($categories as $Category) {
            try {
                $Category->save(new QUI\Users\SystemUser());
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_ERROR);
            }
        }

        // media cleanup
        try {
            $MainFolder = Products::getParentMediaFolder();
        } catch (QUI\Exception $Exception) {
            $MainFolder = QUI::getProjectManager()->getStandard()->getMedia();
        }

        $Media    = $MainFolder->getMedia();
        $childIds = $MainFolder->getChildrenIds();

        foreach ($childIds as $folderId) {
            $Folder = null;

            try {
                // wenn product id nicht existiert, kann der ordner gelöscht werden
                $Folder = $Media->get($folderId);

                Products::getProduct($Folder->getAttribute('name'));
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
                if ($Exception->getCode() == 404 && Utils::isFolder($Folder)) {
                    $Folder->delete();
                }

                QUI\System\Log::write($Exception->getMessage());
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::write($Exception->getMessage());
            }
        }

        // cache cleanup
        QUI\ERP\Products\Search\Cache::clear();
        Categories::clearCache();

        try {
            QUI::getEvents()->fireEvent('onQuiqqerProductsProductCleanup');
        } catch (QUi\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Permission using?
     * Is permission using active?
     *
     * @return boolean
     */
    public static function usePermissions()
    {
        if (!\is_null(self::$usePermissions)) {
            return self::$usePermissions;
        }

        try {
            $Package = QUI::getPackage('quiqqer/products');
            $Config  = $Package->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return false;
        }

        $usePermission        = (int)$Config->get('products', 'usePermissions');
        self::$usePermissions = $usePermission ? true : false;

        return self::$usePermissions;
    }

    /**
     * Set global projects locale
     *
     * @param QUI\Locale $Locale
     */
    public static function setLocale(QUI\Locale $Locale)
    {
        self::$Locale = $Locale;
    }

    /**
     * Return global projects locale
     *
     * @return QUI\Locale
     */
    public static function getLocale()
    {
        if (!self::$Locale) {
            self::$Locale = new QUI\Locale();
            self::$Locale->setCurrent(QUI::getLocale()->getCurrent());
        }

        return self::$Locale;
    }

    //region editable

    /**
     * Return the global overwriteable variant fields
     *
     * @return QUI\ERP\Products\Field\Field[]
     */
    public static function getGlobalEditableVariantFields()
    {
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $result = [];
        $fields = $Config->getSection('editableFields');

        if (empty($fields)) {
            return [];
        }

        foreach ($fields as $fieldId => $active) {
            if (empty($active)) {
                continue;
            }

            try {
                $result[] = Fields::getField($fieldId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $result;
    }

    /**
     * Set global editable variant fields
     *
     * @param array $fieldIds
     *
     * @throws QUI\Exception
     */
    public static function setGlobalEditableVariantFields(array $fieldIds)
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $Config->setSection('editableFields', []);

        foreach ($fieldIds as $field) {
            $Config->setValue('editableFields', $field, 1);
        }

        $Config->save();
    }

    //endregion

    //region inherited


    /**
     * Return the global inherited variant fields
     *
     * @return QUI\ERP\Products\Field\Field[]
     */
    public static function getGlobalInheritedVariantFields()
    {
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $result = [];
        $fields = $Config->getSection('inheritedFields');

        if (empty($fields)) {
            return [];
        }

        foreach ($fields as $fieldId => $active) {
            if (empty($active)) {
                continue;
            }

            try {
                $result[] = Fields::getField($fieldId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $result;
    }

    /**
     * Set global inherited variant fields
     *
     * @param array $fieldIds
     *
     * @throws QUI\Exception
     */
    public static function setGlobalInheritedVariantFields(array $fieldIds)
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $Config->setSection('inheritedFields', []);

        foreach ($fieldIds as $field) {
            $Config->setValue('inheritedFields', $field, 1);
        }

        $Config->save();
    }

    //endregion

    //region cache writing flags

    /**
     * ENABLE: Write product data to database when a product is saved
     *
     * For further information see documentation self::$writeProductDataToDb
     *
     * @return void
     */
    public static function enableGlobalWriteProductDataToDb()
    {
        self::$writeProductDataToDb = true;
    }

    /**
     * DISABLE: Write product data to database when a product is saved
     *
     * For further information see documentation self::$writeProductDataToDb
     *
     * @return void
     */
    public static function disableGlobalWriteProductDataToDb()
    {
        self::$writeProductDataToDb = false;
    }

    /**
     * ENABLE: Fire events when a Product is saved.
     *
     * For further information see documentation of self::$fireEventsOnProductSave
     *
     * @return void
     */
    public static function enableGlobalFireEventsOnProductSave()
    {
        self::$fireEventsOnProductSave = true;
    }

    /**
     * DISABLE: Fire events when a Product is saved.
     *
     * For further information see documentation of self::$fireEventsOnProductSave
     *
     * @return void
     */
    public static function disableGlobalFireEventsOnProductSave()
    {
        self::$fireEventsOnProductSave = false;
    }

    /**
     * ENABLE: Actually write the product search cache if Product->updateCache() is called.
     *
     * For futher information see documentation of self::$updateProductSearchCache
     *
     * @return void
     */
    public static function enableGlobalProductSearchCacheUpdate()
    {
        self::$updateProductSearchCache = true;
    }

    /**
     * DISABLE: Actually write the product search cache if Product->updateCache() is called.
     *
     * For futher information see documentation of self::$updateProductSearchCache
     *
     * @return void
     */
    public static function disableGlobalProductSearchCacheUpdate()
    {
        self::$updateProductSearchCache = false;
    }

    /**
     * ENABLE: Caching of UniqueProduct data during runtime
     *
     * For futher information see documentation of self::$useRuntimeCacheForUniqueProducts
     *
     * @return void
     */
    public static function enableRuntimeCacheForUniqueProducts()
    {
        self::$useRuntimeCacheForUniqueProducts = true;
    }

    /**
     * DISABLE: Caching of UniqueProduct data during runtime
     *
     * For futher information see documentation of self::$useRuntimeCacheForUniqueProducts
     *
     * @return void
     */
    public static function disableRuntimeCacheForUniqueProducts()
    {
        self::$useRuntimeCacheForUniqueProducts = false;
    }

    //endregion
}
