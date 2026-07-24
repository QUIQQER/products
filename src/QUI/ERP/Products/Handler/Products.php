<?php

/**
 * This file contains QUI\ERP\Products\Handler\Products
 */

namespace QUI\ERP\Products\Handler;

use Exception;
use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Interfaces\ProductTypeInterface;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Utils\Tables as TablesUtils;
use QUI\Locale;
use QUI\Projects\Media\Utils;
use QUI\Projects\Media\Utils as FolderUtils;

use function class_exists;
use function count;
use function date;
use function implode;
use function is_null;
use function is_numeric;
use function is_object;
use function json_encode;
use function str_replace;
use function trim;

/**
 * Class Products
 * get and add new products
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
    public static bool $writeProductDataToDb = true;

    /**
     * This global flag determines if the Product search cache is actually written when Product->updateCache()
     * is called.
     *
     * The intention is to disable expensive Product data collection and database queries in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * @var bool
     */
    public static bool $updateProductSearchCache = true;

    /**
     * This global flag determines if Product specific events are fired when a Product is saved.
     *
     * The intention is to disable expensive event handler operations in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * @var bool
     */
    public static bool $fireEventsOnProductSave = true;

    /**
     * This global flag determines if UniqueProduct data is cached in the RAM during runtime
     *
     * The intention is to disable expensive caching operations in a context where a lot of Product
     * objects are processed in a short time (i.e. mass imports).
     *
     * @var bool
     */
    public static bool $useRuntimeCacheForUniqueProducts = true;

    /**
     * This enables the caching flag, equal if quiqqer is in frontend or backend
     *
     * @var bool
     */
    public static bool $createFrontendCache = false;

    /**
     * Product permission using?
     * @var null|boolean
     */
    private static ?bool $usePermissions = null;

    /**
     * List of internal products
     * @var array<mixed>
     */
    /**
     * @var array<int, QUI\ERP\Products\Product\Types\AbstractType>
     */
    private static array $list = [];

    /**
     * Global Product Locale
     *
     * @var Locale|null
     */
    private static ?Locale $Locale = null;

    /**
     * Runtime cache for "extend variant child short description" flag
     *
     * @var bool|null
     */
    private static ?bool $extendVariantChildShortDesc = null;

    /**
     * Runtime cache for "check duplicate article no" flag
     *
     * @var bool|null
     */
    private static ?bool $checkDuplicateArticleNo = null;

    /**
     * Return the main media folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public static function getParentMediaFolder(): QUI\Projects\Media\Folder
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $folderUrl = $Config?->get('products', 'folder');

        if (empty($folderUrl)) {
            throw new QUI\Exception([
                'quiqqer/products',
                'exception.products.media.folder.missing'
            ]);
        }

        try {
            $Folder = FolderUtils::getMediaItemByUrl($folderUrl);

            if ($Folder instanceof QUI\Projects\Media\Folder) {
                return $Folder;
            }
        } catch (QUI\Exception) {
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
    public static function getProduct(int $pid): QUI\ERP\Products\Product\Types\AbstractType
    {
        if (isset(self::$list[$pid])) {
            return self::$list[$pid];
        }

        // Wenn der RAM zu voll wird, Objekte mal leeren
        if (QUI\Utils\System::memUsageToHigh()) {
            self::$list = [];
        }

        // check if serialize product exists
        $cachePath = Cache::getProductCachePath($pid) . '/db-data';

        //if (QUI::isFrontend()) { // -> mor wollte dies raus haben
        try {
            $product = QUI\Cache\LongTermCache::get($cachePath);
            self::$list[$pid] = self::getProductByDataResult($pid, $product);

            return self::$list[$pid];
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
        //        }

        $Product = self::getNewProductInstance($pid);
        self::$list[$pid] = $Product;

        return $Product;
    }

    /**
     * This clean the instance cache for the product manager
     * use this with caution
     *
     * @param int|null $productId (optional) - Only clear instance cache for specific product id
     */
    public static function cleanProductInstanceMemCache(?int $productId = null): void
    {
        if ($productId) {
            unset(self::$list[$productId]);
            return;
        }

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
    public static function getProductByUrl(
        string $url,
        int $category
    ): QUI\ERP\Products\Product\Types\AbstractType {
        $field = 'F' . QUI\ERP\Products\Handler\Fields::FIELD_URL;

        try {
            $result = QUI\ERP\Products\Utils\Database::fetch([
                'select' => [$field, 'category', 'id'],
                'from' => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                'where' => [
                    $field => $url,
                    'category' => [
                        'type' => '%LIKE%',
                        'value' => ',' . $category . ','
                    ]
                ],
                'limit' => 1
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
                    'url' => $url,
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
     * @param int|string $pid
     * @throws QUI\ERP\Products\Product\Exception
     */
    public static function getNewProductInstance($pid): QUI\ERP\Products\Product\Types\AbstractType
    {
        if (!is_numeric($pid)) {
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

        $pid = (int)$pid;

        try {
            $result = QUI\ERP\Products\Utils\Database::fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where' => [
                    'id' => $pid
                ],
                'limit' => 1
            ]);
        } catch (QUI\Exception) {
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
                // if not exists, so we clean up the cache table, too
                QUI::getDataBaseConnection()->delete(
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
            $cachePath = Cache::getProductCachePath($pid) . '/db-data';

            try {
                QUI\Cache\LongTermCache::get($cachePath);
            } catch (QUI\Exception) {
                QUI\Cache\LongTermCache::set($cachePath, $productData);
            }
        }

        $Product = self::getProductByDataResult($pid, $productData);

        self::$list[$pid] = $Product;

        return $Product;
    }

    /**
     * @param mixed $pid
     * @param mixed $result
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * // @todo interface check
     */
    protected static function getProductByDataResult($pid, $result): QUI\ERP\Products\Product\Types\AbstractType
    {
        $type = $result['type'];

        if (empty($type)) {
            $type = QUI\ERP\Products\Product\Types\Product::class;
        }

        $Product = new $type($pid, $result);

        if (!$Product instanceof QUI\ERP\Products\Product\Types\AbstractType) {
            throw new QUI\ERP\Products\Product\Exception('Invalid product type: ' . $type);
        }

        return $Product;
    }

    /**
     * Exists a product?
     *
     * @param integer $pid - Product-ID
     * @return boolean
     */
    public static function existsProduct(int $pid): bool
    {
        if (isset(self::$list[$pid])) {
            return true;
        }

        try {
            $result = QUI\ERP\Products\Utils\Database::fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where' => [
                    'id' => $pid
                ],
                'limit' => 1
            ]);
        } catch (Exception) {
            return false;
        }

        return isset($result[0]);
    }

    /**
     * Get product by product no
     *
     * @param string $productNo - Product-No
     * @return ProductTypeInterface
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public static function getProductByProductNo(string $productNo): ProductTypeInterface
    {
        try {
            $result = QUI\ERP\Products\Utils\Database::fetch([
                'select' => [
                    'id'
                ],
                'from' => TablesUtils::getProductCacheTableName(),
                'where' => [
                    'productNo' => $productNo
                ],
                'limit' => 1
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
     * @param array<mixed> $categories - list of category IDs or category Objects
     * @param array<mixed> $fields - optional, list of fields (Field, Field, Field)
     * @param string $productType - optional, product type
     * @param integer|null $parent - optional, parent product
     * @param bool $validation - optional, should a validation executed? (default=true)
     *
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\Exception
     */
    public static function createProduct(
        array $categories = [],
        array $fields = [],
        string $productType = '',
        null | int $parent = null,
        bool $validation = true
    ): QUI\ERP\Products\Product\Types\AbstractType {
        QUI\Permissions\Permission::checkPermission('product.create');

        // product type
        $type = QUI\ERP\Products\Product\Types\Product::class;

        if (!empty($productType) && $productType !== $type) {
            $productType = trim($productType, '\\');
            $ProductTypes = QUI\ERP\Products\Utils\ProductTypes::getInstance();

            if ($ProductTypes->exists($productType)) {
                $type = $productType;
            }
        }

        // categories
        $categoryIds = [];

        if (empty($categories)) {
            $categoryIds[] = Categories::getMainCategory()->getId();
        }

        foreach ($categories as $Category) {
            if (!is_object($Category)) {
                try {
                    $Category = Categories::getCategory($Category);
                    $categoryIds[] = $Category->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addWarning($Exception->getMessage());
                }

                continue;
            }

            if ($Category instanceof QUI\ERP\Products\Interfaces\CategoryInterface) {
                $categoryIds[] = $Category->getId();
                continue;
            }

            throw new QUI\Exception([
                'quiqqer/products',
                'exception.products.no.category'
            ]);
        }

        if (!count($categoryIds)) {
            throw new QUI\Exception([
                'quiqqer/products',
                'exception.products.no.category.given'
            ]);
        }

        // fields
        $fieldData = [];

        foreach ($fields as $Field) {
            if (!is_object($Field)) {
                try {
                    $Field = Fields::getField($Field);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addWarning($Exception->getMessage());
                    continue;
                }
            }

            if (!$Field instanceof Field) {
                throw new QUI\Exception([
                    'quiqqer/products',
                    'exception.field.is.invalid'
                ]);
            }

            $value = $Field->getValue();

            if ($Field->isRequired()) {
                if ($value === '' && $validation) {
                    throw new QUI\Exception([
                        'quiqqer/products',
                        'exception.field.is.invalid',
                        [
                            'fieldId' => $Field->getId(),
                            'fieldtitle' => $Field->getTitle()
                        ]
                    ]);
                }

                try {
                    $Field->validate($Field->getValue());
                } catch (QUI\Exception $Exception) {
                    if ($validation) {
                        throw $Exception;
                    }
                }
            }

            $fieldData[] = $Field->toProductArray();
        }

        QUI::getDataBaseConnection()->insert(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            [
                'fieldData' => json_encode($fieldData),
                'category' => $categoryIds[0],
                'categories' => ',' . implode(',', $categoryIds) . ',',
                'type' => $type,
                'c_user' => QUI::getUserBySession()->getUUID(),
                'c_date' => date('Y-m-d H:i:s'),
                'parent' => $parent
            ]
        );

        $newId = (int)QUI::getDataBaseConnection()->lastInsertId();

        if (class_exists('\QUI\Watcher')) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.create', [
                    'id' => $newId
                ]),
                '',
                [
                    'fieldData' => $fieldData,
                    'categories' => ',' . implode(',', $categoryIds) . ','
                ]
            );
        }

        $Product = self::getNewProductInstance($newId);

        if (!($Product instanceof VariantChild)) {
//            $Product->getField(QUI\ERP\Products\Handler\Fields::FIELD_FOLDER)->setValue('');
            $Product->createMediaFolder(); // the product is also saved in this method
        }

        // Auto-generate article no.
        $isAutoGenerateArticleNo = self::isAutoGenerateArticleNo();
        $ArticleNoField = $Product->getField(Fields::FIELD_PRODUCT_NO);

        if (
            $isAutoGenerateArticleNo &&
            empty($ArticleNoField->getValue())
        ) {
            $ArticleNoField->setValue(self::generateArticleNo($Product));
        }

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCreate', [$Product]);

        $Product->save();
        self::$list[$newId] = $Product;

        return $Product;
    }

    /**
     * Copy a product
     *
     * @param integer $productId
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\Exception
     */
    public static function copyProduct(int $productId): QUI\ERP\Products\Product\Types\AbstractType
    {
        $Product = self::getProduct($productId);
        $fields = $Product->getFields();

        // filter url quiqqer/products#301
        $fields = array_filter($fields, function ($Field) {
            return $Field->getId() !== Fields::FIELD_URL;
        });

        $parent = null;

        if ($Product instanceof VariantChild) {
            $parent = $Product->getParent()->getId();
        }

        $New = self::createProduct(
            $Product->getCategories(),
            $fields,
            $Product->getType(),
            $parent,
            false
        );

        $New->setPermissions($Product->getPermissions());
        $MainCategory = $Product->getCategory();

        if ($MainCategory !== null) {
            $New->setMainCategory($MainCategory);
        }

        $folders = $New->getFieldsByType(Fields::TYPE_FOLDER);

        // @todo sub media folder kopieren wäre sinnvoller.
        // vorerst leer machen, so wird dann ein neuer ordner erstellt

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
     * @param array<mixed> $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array<mixed>
     */
    public static function getProducts(array $queryParams = []): array
    {
        $result = [];
        $data = self::getProductIds($queryParams);

        foreach ($data as $id) {
            try {
                $result[] = self::getProduct($id);
            } catch (QUI\Exception) {
            }
        }

        return $result;
    }

    /**
     * Return a list of product ids
     * if $queryParams is empty, all products are returned
     *
     * @param array<mixed> $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array<mixed>
     */
    public static function getProductIds(array $queryParams = []): array
    {
        $query = [
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName()
        ];

        $allowedFields = [
            'id',
            'type',
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

        if (
            isset($queryParams['where']) &&
            QUI\ERP\Products\Utils\Database::areConditionsValid($queryParams['where'], $allowedFields)
        ) {
            $query['where'] = $queryParams['where'];
        }

        if (
            isset($queryParams['where_or']) &&
            QUI\ERP\Products\Utils\Database::areConditionsValid($queryParams['where_or'], $allowedFields)
        ) {
            $query['where_or'] = $queryParams['where_or'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        if (
            isset($queryParams['order']) &&
            QUI\ERP\Products\Utils\Database::isOrderValid($queryParams['order'], $allowedFields)
        ) {
            $query['order'] = $queryParams['order'];
        }

        if (isset($queryParams['debug'])) {
            $query['debug'] = $queryParams['debug'];
        }

        /*
         * This is done because we only need the ID, otherwise all data may be queried from the table
         * which kills the RAM.
         */
        $query['select'] = 'id';

        $result = [];

        try {
            $data = QUI\ERP\Products\Utils\Database::fetch($query);
        } catch (Exception $Exception) {
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
    public static function deleteProduct(int $pid): void
    {
        self::getProduct($pid)->delete();
    }

    /**
     * Return the number of the products
     * Count products
     *
     * @param array<mixed> $queryParams - query params (where, where_or)
     * @return integer
     */
    public static function countProducts(array $queryParams = []): int
    {
        $query = [
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
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
            $data = QUI\ERP\Products\Utils\Database::fetch($query);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return 0;
        }

        if (isset($data[0]['count'])) {
            return (int)$data[0]['count'];
        }

        return 0;
    }

    /**
     * Cleanup all products
     * @throws QUI\Exception
     */
    public static function cleanup(): void
    {
        if (class_exists('\QUI\Watcher')) {
            QUI\Watcher::$globalWatcherDisable = true;
        }

        // cache cleanup
        QUI\ERP\Products\Search\Cache::clear();
        Categories::clearCache();

        $ids = self::getProductIds();
        $SystemUser = QUI::getUsers()->getSystemUser();

        foreach ($ids as $id) {
            try {
                $Product = self::getNewProductInstance($id);
                $Product->save($SystemUser);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // kategorien
        $categories = Categories::getCategories();

        /* @var $Category Category */
        foreach ($categories as $Category) {
            try {
                $Category->save($SystemUser);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // media cleanup
        try {
            $MainFolder = Products::getParentMediaFolder();
            $Media = $MainFolder->getMedia();
            $childIds = $MainFolder->getChildrenIds();
        } catch (QUI\Exception) {
            $Project = QUI::getProjectManager()->getStandard();

            if ($Project === null) {
                throw new QUI\Exception('Standard project is unavailable.');
            }

            $Media = $Project->getMedia();
            $childIds = $Media->getChildrenIds();
        }

        if (!is_array($childIds)) {
            $childIds = [];
        }

        foreach ($childIds as $folderId) {
            $Folder = null;

            try {
                // wenn product id nicht existiert, kann der ordner gelöscht werden
                $Folder = $Media->get($folderId);

                Products::getProduct($Folder->getAttribute('name'));
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
                if ($Exception->getCode() == 404 && Utils::isFolder($Folder)) {
                    $Folder?->delete();
                }

                QUI\System\Log::writeException($Exception);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Delete all product IDs from the products_cache that do not exist anymore
        $QueryBuilder = QUI::getQueryBuilder()
            ->delete(QUI\Utils\Doctrine::quoteIdentifier(
                QUI\ERP\Products\Utils\Tables::getProductCacheTableName()
            ));

        if ($ids !== []) {
            $placeholders = [];

            foreach (array_values($ids) as $index => $id) {
                $parameter = 'existingProduct' . $index;
                $placeholders[] = ':' . $parameter;
                $QueryBuilder->setParameter($parameter, $id);
            }

            $QueryBuilder->where($QueryBuilder->expr()->notIn(
                QUI\Utils\Doctrine::quoteIdentifier('id'),
                $placeholders
            ));
        }

        $QueryBuilder->executeStatement();

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
     * @return bool|null
     */
    public static function usePermissions(): ?bool
    {
        if (!is_null(self::$usePermissions)) {
            return self::$usePermissions;
        }

        try {
            $Package = QUI::getPackage('quiqqer/products');
            $Config = $Package->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return false;
        }

        $usePermission = (int)$Config?->get('products', 'usePermissions');
        self::$usePermissions = (bool)$usePermission;

        return self::$usePermissions;
    }

    /**
     * Set global projects locale
     *
     * @param Locale $Locale
     */
    public static function setLocale(Locale $Locale): void
    {
        self::$Locale = $Locale;
    }

    /**
     * Return global projects locale
     *
     * @return Locale
     */
    public static function getLocale(): Locale
    {
        if (!self::$Locale) {
            self::$Locale = new Locale();
            self::$Locale->setCurrent(QUI::getLocale()->getCurrent());
        }

        return self::$Locale;
    }

    //region editable

    /**
     * Return the global overwrite variant fields
     *
     * @return QUI\ERP\Products\Field\Field[]
     */
    public static function getGlobalEditableVariantFields(): array
    {
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $result = [];
        $fields = $Config?->getSection('editableFields');

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
     * @param array<mixed> $fieldIds
     *
     * @throws QUI\Exception
     */
    public static function setGlobalEditableVariantFields(array $fieldIds): void
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $Config?->setSection('editableFields');

        foreach ($fieldIds as $field) {
            $Config?->setValue('editableFields', $field, 1);
        }

        $Config?->save();
    }

    //endregion

    //region inherited


    /**
     * Return the global inherited variant fields
     *
     * @return QUI\ERP\Products\Field\Field[]
     */
    public static function getGlobalInheritedVariantFields(): array
    {
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $result = [];
        $fields = $Config?->getSection('inheritedFields');

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
     * @param array<mixed> $fieldIds
     *
     * @throws QUI\Exception
     */
    public static function setGlobalInheritedVariantFields(array $fieldIds): void
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $Config?->setSection('inheritedFields');

        foreach ($fieldIds as $field) {
            $Config?->setValue('inheritedFields', $field, 1);
        }

        $Config?->save();
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
    public static function enableGlobalWriteProductDataToDb(): void
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
    public static function disableGlobalWriteProductDataToDb(): void
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
    public static function enableGlobalFireEventsOnProductSave(): void
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
    public static function disableGlobalFireEventsOnProductSave(): void
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
    public static function enableGlobalProductSearchCacheUpdate(): void
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
    public static function disableGlobalProductSearchCacheUpdate(): void
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
    public static function enableRuntimeCacheForUniqueProducts(): void
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
    public static function disableRuntimeCacheForUniqueProducts(): void
    {
        self::$useRuntimeCacheForUniqueProducts = false;
    }

    //endregion

    // region Auto-generated article nos.

    /**
     * Auto-generate a new article no.
     *
     * @param QUI\ERP\Products\Product\Product $Product
     * @return string
     * @throws QUI\Database\Exception
     */
    public static function generateArticleNo(QUI\ERP\Products\Product\Product $Product): string
    {
        $NumberRange = new QUI\ERP\Products\NumberRange();
        $nextId = $NumberRange->getRange();

        try {
            $Conf = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return (string)$nextId;
        }

        $articleNoConf = $Conf?->getSection('autoArticleNos');

        if (!empty($articleNoConf['prefix'])) {
            $nextId = $articleNoConf['prefix'] . $nextId;
        }

        if (!empty($articleNoConf['suffix'])) {
            $nextId .= $articleNoConf['suffix'];
        }

        // Category
        $mainCategoryId = $Product->getCategory()?->getId() ?? '';

        if (empty($mainCategoryId)) {
            $mainCategoryId = '';
        }

        // replace placeholders
        $replacements = array_map('strval', [
            date('Y'),
            date('m'),
            date('d'),
            $mainCategoryId
        ]);

        return str_replace(
            [
                '#YEAR',
                '#MONTH',
                '#DAY',
                '#CAT_ID'
            ],
            $replacements,
            (string)$nextId
        );
    }

    /**
     * Are product article no. automatically generated?
     *
     * @return bool
     */
    public static function isAutoGenerateArticleNo(): bool
    {
        try {
            $Conf = QUI::getPackage('quiqqer/products')->getConfig();
            return !empty($Conf?->get('autoArticleNos', 'generate'));
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return false;
    }

    // endregion

    /**
     * Shall short descriptions of variant children be automatically extended by
     * attribute list field titles/values?
     *
     * @return bool
     */
    public static function isExtendVariantChildShortDesc(): bool
    {
        if (!is_null(self::$extendVariantChildShortDesc)) {
            return self::$extendVariantChildShortDesc;
        }

        try {
            $Conf = QUI::getPackage('quiqqer/products')->getConfig();

            self::$extendVariantChildShortDesc = !empty($Conf?->get('variants', 'extendShortDesc'));

            return self::$extendVariantChildShortDesc;
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return false;
    }

    /**
     * Shall the check for duplicate article nos be executed on product save?
     *
     * @return bool
     */
    public static function isCheckDuplicteArticleNo(): bool
    {
        if (!is_null(self::$checkDuplicateArticleNo)) {
            return self::$checkDuplicateArticleNo;
        }

        try {
            $Conf = QUI::getPackage('quiqqer/products')->getConfig();

            self::$checkDuplicateArticleNo = !empty($Conf?->get('products', 'checkDuplicateArticleNo'));

            return self::$checkDuplicateArticleNo;
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return true;
    }
}
