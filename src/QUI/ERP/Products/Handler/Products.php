<?php

/**
 * This file contains QUI\ERP\Products\Handler\Products
 */
namespace QUI\ERP\Products\Handler;

use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Field\Field;
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
     * Product permission using?
     * @var null|boolean
     */
    private static $usePermissions = null;

    /**
     * List of internal products
     * @var array
     */
    private static $list = array();

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
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.products.media.folder.missing'
            ));
        }

        try {
            $Folder = FolderUtils::getMediaItemByUrl($folderUrl);

            if (FolderUtils::isFolder($Folder)) {
                return $Folder;
            }

        } catch (QUI\Exception $Exception) {
        }

        throw new QUI\Exception(array(
            'quiqqer/products',
            'exception.products.media.folder.missing'
        ));
    }

    /**
     * @param integer $pid - Product-ID
     * @return QUI\ERP\Products\Product\Product
     *
     * @throw QUI\ERP\Products\Product\Exception
     */
    public static function getProduct($pid)
    {
        if (isset(self::$list[$pid])) {
            return self::$list[$pid];
        }

        $Product          = new QUI\ERP\Products\Product\Product($pid);
        self::$list[$pid] = $Product;

        return $Product;
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

        $result = QUI::getDataBase()->fetch(array(
            'from'  => QUI\ERP\Products\Utils\Tables::getProductTableName(),
            'where' => array(
                'id' => $pid
            )
        ));

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
            $result = QUI::getDataBase()->fetch(array(
                'select' => array(
                    'id'
                ),
                'from'   => TablesUtils::getProductCacheTableName(),
                'where'  => array(
                    'productNo' => $productNo
                ),
                'limit'  => 1
            ));

        } catch (QUI\Exception $Exception) {
            // TODO: mit Mor besprechen
            QUI\System\Log::addError(
                $Exception->getMessage()
            );

            throw new QUI\Exception(
                array(
                    'quiqqer/products',
                    'exception.get.product.by.no.error'
                ),
                $Exception->getCode(),
                array(
                    'productNo' => $productNo
                )
            );
        }

        if (empty($result)
            || !isset($result[0]['id'])
        ) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.product.no.not.found'),
                404,
                array(
                    'productNo' => $productNo
                )
            );
        }

        return self::getProduct($result[0]['id']);
    }

    /**
     * Create a new Product
     *
     * @param array $categories - list of category IDs or category Objects
     * @param array $fields - optional, list of fields (Field, Field, Field)
     *
     * @return QUI\ERP\Products\Product\Product
     *
     * @throws QUI\Exception
     */
    public static function createProduct(
        $categories = array(),
        $fields = array()
    ) {
        QUI\Permissions\Permission::checkPermission('product.create');

        // categories
        $categoryids = array();

        if (empty($categories)) {
            $categoryids[] = Categories::getMainCategory();
        }

        foreach ($categories as $Category) {
            if (!is_object($Category)) {
                try {
                    $Category      = Categories::getCategory($Category);
                    $categoryids[] = $Category->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addWarning($Exception->getMessage());
                }

                continue;
            }

            if (Categories::isCategory($Category)) {
                /* @var $Category Category */
                $categoryids[] = $Category->getId();
                continue;
            }

            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.products.no.category'
            ));
        }

        if (!count($categoryids)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.products.no.category.given'
            ));
        }

        // fields
        $fieldData = array();

        /* @var $Field Field|integer */
        foreach ($fields as $Field) {
            if (!is_object($Field)) {
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
                    throw new QUI\Exception(array(
                        'quiqqer/products',
                        'exception.field.is.invalid',
                        array(
                            'fieldId'    => $Field->getId(),
                            'fieldtitle' => $Field->getTitle()
                        )
                    ));
                }

                $Field->validate($Field->getValue());
            }

            $fieldData[] = $Field->toProductArray();
        }


        QUI::getDataBase()->insert(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array(
                'fieldData'  => json_encode($fieldData),
                'categories' => ',' . implode($categoryids, ',') . ','
            )
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.create', array(
                'id' => $newId
            )),
            '',
            array(
                'fieldData'  => $fieldData,
                'categories' => ',' . implode($categoryids, ',') . ','
            )
        );


        $Product = self::getProduct($newId);
        $Product->updateCache();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCreate', array($Product));

        return $Product;
    }

    /**
     * Copy a product
     *
     * @param integer $productId
     * @return QUI\ERP\Products\Product\Product
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

        // @todo titel setzen -> Kopie von

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCopy', array($Product));

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
    public static function getProducts($queryParams = array())
    {
        $result = array();
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
    public static function getProductIds($queryParams = array())
    {
        $query = array(
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName()
        );

        $allowedFields = array(
            'id',
            'category',
            'categories',
            'fieldData',
            'active',
            'parent',
            'permissions'
        );

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

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

        foreach ($data as $entry) {
            try {
                $result[] = $entry['id'];
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * @param integer $pid
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
    public static function countProducts($queryParams = array())
    {
        $query = array(
            'from'  => QUI\ERP\Products\Utils\Tables::getProductTableName(),
            'count' => array(
                'select' => 'id',
                'as'     => 'count'
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
        $MainFolder = Products::getParentMediaFolder();
        $Media      = $MainFolder->getMedia();
        $childIds   = $MainFolder->getChildrenIds();

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

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCleanup');
    }

    /**
     * Permission using?
     * Is permission using active?
     *
     * @return boolean
     */
    public static function usePermissions()
    {
        if (!is_null(self::$usePermissions)) {
            return self::$usePermissions;
        }

        $Package = QUI::getPackage('quiqqer/products');
        $Config  = $Package->getConfig();

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
}
