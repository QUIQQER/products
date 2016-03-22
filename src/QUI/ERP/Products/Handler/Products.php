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

/**
 * Class Products
 * get and add new products
 *
 * @package QUI\ERP\Products\Handler
 */
class Products
{
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
     * @throw QUI\Exception
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
        QUI\Rights\Permission::checkPermission('product.create');

        // categories
        $categoryids = array();

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
                if (empty($value)) {
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
                'categories' => ',' . implode($categories, ',') . ','
            )
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();

        // translation - title
        try {
            QUI\Translator::addUserVar(
                'quiqqer/products',
                'products.product.' . $newId . '.title',
                array(
                    'datatype' => 'js,php'
                )
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());

            QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }

        return self::getProduct($newId);
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
     * if $queryParams is empty, all fields are returned
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
