<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantParent
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Field\Types\AttributeGroup;
use QUI\ERP\Products\Field\Types\ProductAttributeList;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Interfaces\FieldInterface as Field;
use QUI\ERP\Products\Product\Exception;
use QUI\ERP\Products\Utils\Tables;

use function array_map;
use function array_merge;
use function array_search;
use function array_unique;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function json_decode;
use function json_encode;
use function mb_strtolower;
use function trim;

/**
 * Class Variant
 * - Variant Parent
 *
 * This is a variant parent product
 *
 * @package QUI\ERP\Products\Product\Types
 *
 * backend
 * @todo beim speichern der daten, refresh der daten -> am besten produkt daten als ergebnis mitliefern
 *
 * frontend
 * @todo canonical auf variante wenn variant=id
 */
class VariantParent extends AbstractType
{
    /**
     * Variant generation : Delete all children and create the new ons
     */
    const GENERATION_TYPE_RESET = 1;

    /**
     * Variant generation : Adds only new ones
     */
    const GENERATION_TYPE_ADD = 2;

    /**
     * @var null
     */
    protected $children = null;

    /**
     * @var array
     */
    protected $childFields = null;

    /**
     * @var array
     */
    protected $childFieldsActive = null;
    /**
     * @var array
     */
    protected $childFieldHashes = null;

    /**
     * Model constructor
     *
     * @param integer $pid - Product-ID
     * @param array $product - Product Data
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function __construct($pid, $product = [])
    {
        parent::__construct($pid, $product);

        $editable = $this->getAttribute('editableVariantFields');

        foreach ($this->fields as $Field) {
            // pcsg-projects/demo-shop/-/issues/9#note_152341
            if (is_array($editable) && in_array($Field->getId(), $editable)) {
                continue;
            }

            if ($Field instanceof AttributeGroup) {
                $Field->clearValue();
                $Field->setDefaultValue(null);
            }
        }
    }

    //region abstract type methods

    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeTitle($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.parent.title');
    }

    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeDescription($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.parent.title');
    }

    /**
     * Returns the backend panel control
     */
    public static function getTypeBackendPanel()
    {
        return 'package/quiqqer/products/bin/controls/products/ProductVariant';
    }

    //endregion

    //region overwritten product methods

    /**
     * Internal saving method
     *
     * @param array $fieldData - field data
     * @param null|QUI\Interfaces\Users\User $EditUser
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     * @throws Exception
     */
    protected function productSave($fieldData, $EditUser = null)
    {
        QUI\Permissions\Permission::checkPermission('product.edit', $EditUser);

        $editableAttribute  = $this->getAttribute('editableVariantFields');
        $inheritedAttribute = $this->getAttribute('inheritedVariantFields');

        $data = [];

        if (is_array($editableAttribute)) {
            $editable = [];

            // check if fields exists
            foreach ($editableAttribute as $fieldId) {
                try {
                    $editable[] = FieldHandler::getField($fieldId)->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }

            $data['editableVariantFields'] = json_encode($editable);
        } else {
            $data['editableVariantFields'] = '';
        }

        if (is_array($inheritedAttribute)) {
            $inherited = [];

            // check if fields exists
            foreach ($inheritedAttribute as $fieldId) {
                try {
                    $inherited[] = FieldHandler::getField($fieldId)->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }

            $data['inheritedVariantFields'] = json_encode($inherited);
        } else {
            $data['inheritedVariantFields'] = '';
        }

        if ($this->getDefaultVariantId()) {
            $data['defaultVariantId'] = $this->getDefaultVariantId();
        } else {
            $data['defaultVariantId'] = null;
        }

        if (!empty($data)) {
            QUI::getDataBase()->update(
                QUI\ERP\Products\Utils\Tables::getProductTableName(),
                $data,
                ['id' => $this->getId()]
            );
        }

        parent::productSave($fieldData, $EditUser);

        // Update category values for children
        $categories   = $this->getCategories();
        $MainCategory = $this->getCategory();

        $mainCategoryValue = $MainCategory ? $MainCategory->getId() : null;
        $categoriesValue   = null;

        if (!empty($categories)) {
            $catIds = [];

            /** @var Category $Category */
            foreach ($categories as $Category) {
                $catIds[] = $Category->getId();
            }

            $categoriesValue = ',' . implode(',', $catIds) . ',';
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            [
                'category'   => $mainCategoryValue,
                'categories' => $categoriesValue
            ],
            [
                'parent' => $this->getId()
            ]
        );
    }

    /**
     * Write cache entry for variant parent.
     *
     * @param $lang
     * @return void
     * @throws QUI\Exception
     */
    protected function writeCacheEntry($lang)
    {
        parent::writeCacheEntry($lang);

        // Set category IDs in
        $categories    = $this->getCategories();
        $categoryValue = null;

        if (!empty($categories)) {
            $catIds = [];

            /** @var Category $Category */
            foreach ($categories as $Category) {
                $catIds[] = $Category->getId();
            }

            $categoryValue = ',' . implode(',', $catIds) . ',';
        }

        // Update cache table
        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            [
                'category' => $categoryValue
            ],
            [
                'parentId' => $this->getId(),
                'lang'     => $lang
            ]
        );
    }

    /**
     * delete the complete product with its children
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI\Permissions\Permission::checkPermission('product.delete');

        // delete children
        $children = $this->getVariants();

        foreach ($children as $Child) {
            try {
                $Child->delete();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // delete itself
        parent::delete();
    }

    /**
     * Gets the current product price.
     *
     * This is the price displayed in the frontend to the user. In moste cases,
     * this is equal to the minimum price.
     *
     * @param QUI\Interfaces\Users\User $User (optional)
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getCurrentPrice($User = null)
    {
        if ($this->getDefaultVariantId() === false) {
            return parent::getCurrentPrice($User);
        }

        return $this->getDefaultVariant()->getMinimumPrice($User);
    }

    /**
     * Return the maximum price
     *
     * @param null $User
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getMaximumPrice($User = null)
    {
        // kinder ids
        $children = QUI::getDataBase()->fetch([
            'select' => ['id', 'parent'],
            'from'   => Tables::getProductTableName(),
            'where'  => [
                'parent' => $this->getId()
            ]
        ]);

        $childrenIds = array_map(function ($variant) {
            return $variant['id'];
        }, $children);

        $maxPrices = false;

        // filter
        if (!empty($childrenIds)) {
            $maxPrices = QUI::getDataBase()->fetch([
                'select' => 'id, maxPrice, active',
                'from'   => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                'where'  => [
                    'id'       => [
                        'type'  => 'IN',
                        'value' => $childrenIds
                    ],
                    'maxPrice' => [
                        'type'  => 'NOT',
                        'value' => null
                    ],
                    'active'   => 1
                ],
                'order'  => 'maxPrice DESC',
                'limit'  => 1
            ]);
        }

        if (empty($maxPrices)) {
            return parent::getMaximumPrice($User);
        }

        if (!$User) {
            $User = QUI::getUsers()->getNobody();
        }

        $isNetto = QUI\ERP\Utils\User::isNettoUser($User);

        if (!$isNetto) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);

            $maxPrices[0]['maxPrice'] = $Calc->getPrice($maxPrices[0]['maxPrice']);
        }

        return new QUI\ERP\Money\Price(
            (float)$maxPrices[0]['maxPrice'],
            $this->getCurrency() ?: QUI\ERP\Currency\Handler::getDefaultCurrency(),
            $User
        );
    }

    /**
     * @param null $User
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getMinimumPrice($User = null)
    {
        // kinder ids
        $children = QUI::getDataBase()->fetch([
            'select' => ['id', 'parent'],
            'from'   => Tables::getProductTableName(),
            'where'  => [
                'parent' => $this->getId()
            ]
        ]);

        $childrenIds = array_map(function ($variant) {
            return $variant['id'];
        }, $children);

        // filter
        $minPrices = false;

        if (!empty($childrenIds)) {
            $minPrices = QUI::getDataBase()->fetch([
                'select' => 'id, minPrice, active',
                'from'   => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                'where'  => [
                    'id'       => [
                        'type'  => 'IN',
                        'value' => $childrenIds
                    ],
                    'minPrice' => [
                        'type'  => 'NOT',
                        'value' => null
                    ],
                    'active'   => 1
                ],
                'order'  => 'minPrice ASC',
                'limit'  => 1
            ]);
        }

        if (empty($minPrices)) {
            return parent::getMinimumPrice($User);
        }

        if (!$User) {
            $User = QUI::getUsers()->getNobody();
        }

        $isNetto = QUI\ERP\Utils\User::isNettoUser($User);

        if (!$isNetto) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);

            $minPrices[0]['minPrice'] = $Calc->getPrice($minPrices[0]['minPrice']);
        }

        return new QUI\ERP\Money\Price(
            (float)$minPrices[0]['minPrice'],
            $this->getCurrency() ?: QUI\ERP\Currency\Handler::getDefaultCurrency(),
            $User
        );
    }

    /**
     * Return all images of the product
     * The Variant Parent return all images of the children, too
     *
     * @param array $params - optional, select params
     * @return array
     */
    public function getImages($params = [])
    {
        $cache = QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId()) . '/images';

        if (QUI::isFrontend()) {
            try {
                $images = QUI\Cache\LongTermCache::get($cache);
                $result = [];

                foreach ($images as $image) {
                    try {
                        $result[] = QUI\Projects\Media\Utils::getImageByUrl($image);
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::writeDebugException($Exception);
                    }
                }

                return $result;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        $images   = [];
        $children = $this->getVariants();

        try {
            $images = $this->getMediaFolder()->getImages($params);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        try {
            $Config               = QUI::getPackage('quiqqer/products')->getConfig();
            $parentHasChildImages = !!$Config->getValue('variants', 'parentHasChildImages');
        } catch (QUI\Exception $Exception) {
            $parentHasChildImages = true;
        }

        if ($parentHasChildImages) {
            foreach ($children as $Child) {
                try {
                    $childImages = $Child->getMediaFolder()->getImages($params);
                    $images      = array_merge($images, $childImages);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());
                }
            }
        }

        /* @var $Image QUI\Projects\Media\Image */
        $imageCache = [];
        $indexCheck = [];

        foreach ($images as $Image) {
            $url = $Image->getUrl();

            if (isset($indexCheck[$url])) {
                continue;
            }

            $imageCache[]     = $url;
            $indexCheck[$url] = true;
        }

        QUI\Cache\LongTermCache::set($cache, $imageCache);

        return $images;
    }

    /**
     * Add a field to the product
     *
     * @param Field $Field
     *
     * @throws QUI\Exception
     */
    public function addField(Field $Field)
    {
        $fieldId = $Field->getId();

        $Field->setUnassignedStatus(false);

        $editableAttribute  = QUI\ERP\Products\Utils\Products::getEditableFieldIdsForProduct($this);
        $inheritedAttribute = QUI\ERP\Products\Utils\Products::getInheritedFieldIdsForProduct($this);

        if (!in_array($fieldId, $editableAttribute)) {
            $editableAttribute[] = $fieldId;
        }

        if (!in_array($fieldId, $inheritedAttribute)) {
            $inheritedAttribute[] = $fieldId;
        }

        $this->setAttribute('editableVariantFields', $editableAttribute);
        $this->setAttribute('inheritedVariantFields', $inheritedAttribute);

        parent::addField($Field);

        $children = $this->getVariants();

        foreach ($children as $Child) {
            $Child->addField($Field);
            $Child->save();
        }
    }

    /**
     * Add a field to the product
     *
     * @param Field $Field
     *
     * @throws QUI\Exception
     */
    public function removeField(Field $Field)
    {
        parent::removeField($Field);

        $children = $this->getVariants();

        foreach ($children as $Child) {
            $Child->removeField($Field);
            $Child->save();
        }
    }

//    /**
//     * Write cache entry for product for specific language
//     *
//     * @param string $lang
//     * @throws QUI\Exception
//     */
//    protected function writeCacheEntry($lang)
//    {
//        $Locale = new QUI\Locale();
//        $Locale->setCurrent($lang);
//
//        // wir nutzen system user als netto user
//        $SystemUser = QUI::getUsers()->getSystemUser();
//        $minPrice   = 0;//$this->getMinimumPrice($SystemUser)->value();
//        $maxPrice   = 0;//$this->getMaximumPrice($SystemUser)->value();
//
//        // Dates
//        $cDate = $this->getAttribute('c_date');
//
//        if (empty($cDate) || $cDate === '0000-00-00 00:00:00') {
//            $cDate = date('Y-m-d H:i:s');
//        }
//
//        $eDate = $this->getAttribute('e_date');
//
//        if (empty($eDate) || $eDate === '0000-00-00 00:00:00') {
//            $eDate = date('Y-m-d H:i:s');
//        }
//
//        // type
//        $type         = QUI\ERP\Products\Product\Types\Product::class;
//        $productType  = $this->getAttribute('type');
//        $ProductTypes = QUI\ERP\Products\Utils\ProductTypes::getInstance();
//
//        if ($ProductTypes->exists($productType)) {
//            $type = $productType;
//        }
//
//        $data = [
//            'type'        => $type,
//            'productNo'   => $this->getFieldValueByLocale(
//                FieldHandler::FIELD_PRODUCT_NO,
//                $Locale
//            ),
//            'title'       => $this->getFieldValueByLocale(
//                FieldHandler::FIELD_TITLE,
//                $Locale
//            ),
//            'description' => $this->getFieldValueByLocale(
//                FieldHandler::FIELD_SHORT_DESC,
//                $Locale
//            ),
//            'active'      => $this->isActive() ? 1 : 0,
//            'minPrice'    => $minPrice ? $minPrice : 0,
//            'maxPrice'    => $maxPrice ? $maxPrice : 0,
//            'c_date'      => $cDate,
//            'e_date'      => $eDate
//        ];
//
//        // permissions
//        $permissions     = $this->getPermissions();
//        $viewPermissions = null;
//
//        if (isset($permissions['permission.viewable']) && !empty($permissions['permission.viewable'])) {
//            $viewPermissions = ','.$permissions['permission.viewable'].',';
//        }
//
//        $data['viewUsersGroups'] = $viewPermissions;
//
//        // get all categories
//        $categories = $this->getCategories();
//
//        if (!empty($categories)) {
//            $catIds = [];
//
//            /** @var QUI\ERP\Products\Category\Category $Category */
//            foreach ($categories as $Category) {
//                $catIds[] = $Category->getId();
//            }
//
//            $data['category'] = ','.\implode(',', $catIds).',';
//        } else {
//            $data['category'] = null;
//        }
//
//        // VariantParent fields
//        $fields       = $this->getFields();
//        $searchFields = [];
//
//        /** @var QUI\ERP\Products\Field\Field $Field */
//        foreach ($fields as $Field) {
//            if (!$Field->isSearchable()) {
//                continue;
//            }
//
//            $columnType = mb_strtolower($Field->getColumnType());
//
//            if (mb_strpos($columnType, 'text') === false
//                && mb_strpos($columnType, 'char') === false) {
//                continue;
//            }
//
//            $fieldColumnName = SearchHandler::getSearchFieldColumnName($Field);
//            $searchValue     = $Field->getSearchCacheValue($Locale);
//
//            $data[$fieldColumnName] = $searchValue;
//
//            if ($Field->getId() == FieldHandler::FIELD_PRIORITY
//                && empty($data[$fieldColumnName])
//            ) {
//                // in 10 Jahren darf mor das fixen xD
//                // null und 0 wird als letztes angezeigt
//                $data[$fieldColumnName] = 999999;
//            }
//
//            $searchFields[$Field->getId()] = [
//                'column' => $fieldColumnName,
//                'values' => [
//                    $searchValue => true
//                ]
//            ];
//        }
//
//        // Field values of all VariantChildren
//        /**
//         * If the VariantParent shall also be found when searching for values of its VariantChildren
//         * the search cache entries have to include all child values as well.
//         */
//        if (QUI::getPackage('quiqqer/products')->getConfig()->get('variants', 'findVariantParentByChildValues')) {
//            $result = QUI::getDataBase()->fetch([
//                'select' => ['id', 'fieldData'],
//                'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
//                'where'  => [
//                    'parent' => $this->getId(),
//                    'active' => 1
//                ]
//            ]);
//
//            foreach ($result as $row) {
//                $fields = json_decode($row['fieldData'], true);
//
//                foreach ($fields as $fieldData) {
//                    $fieldId = $fieldData['id'];
//                    $value   = $fieldData['value'];
//
//                    // Only parse children fields that are put in the search table
//                    if (!isset($searchFields[$fieldId])) {
//                        continue;
//                    }
//
//                    $fieldColumnName = $searchFields[$fieldId]['column'];
//                    $Field           = FieldHandler::getField($fieldId);
//
//                    // Only parse children fields that have a non-numeric (i.e. textual) search cache value
//                    switch ($Field->getSearchType()) {
//                        case SearchHandler::SEARCHDATATYPE_NUMERIC:
//                            continue 2;
//                            break;
//                    }
//
//                    $Field->setValue($value);
//
//                    $searchValue = $Field->getSearchCacheValue($Locale);
//
//                    // Do not add duplicate search cache values
//                    if (isset($searchFields[$fieldId]['values'][$searchValue])) {
//                        continue;
//                    }
//
//                    $data[$fieldColumnName] .= ' '.$searchValue;
//
//                    $searchFields[$fieldId]['values'][$searchValue] = true;
//                }
//            }
//        }
//
//        // Prepare data for INSERT
//        foreach ($data as $k => $v) {
//            if (\is_array($v)) {
//                $data[$k] = \json_encode($v);
//            } elseif (\is_string($v)) {
//                $data[$k] = trim($v);
//            }
//        }
//
//        // test if cache entry exists first
//        $result = QUI::getDataBase()->fetch([
//            'from'  => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
//            'where' => [
//                'id'   => $this->getId(),
//                'lang' => $lang
//            ]
//        ]);
//
//        if (empty($result)) {
//            $data['id']   = $this->id;
//            $data['lang'] = $lang;
//
//            QUI::getDataBase()->insert(
//                QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
//                $data
//            );
//
//            return;
//        }
//
//        QUI::getDataBase()->update(
//            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
//            $data,
//            [
//                'id'   => $this->getId(),
//                'lang' => $lang
//            ]
//        );
//    }

    //endregion

    /**
     * Return all variants
     *
     * @param array $params - query params
     * @return QUI\ERP\Products\Product\Types\VariantChild[]|integer
     *
     * @todo cache
     */
    public function getVariants(array $params = [])
    {
        if ($this->children !== null) {
            if (isset($params['count'])) {
                return count($this->children);
            }

            return $this->children;
        }

        try {
            $query = [
                'select' => '*',
                'from'   => Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId()
                ]
            ];

            if (isset($params['limit'])) {
                $query['limit'] = $params['limit'];
            }

            if (isset($params['order'])) {
                switch (mb_strtolower($params['order'])) {
                    case 'active':
                    case 'active asc':
                    case 'active desc':
                    case 'id':
                    case 'id asc':
                    case 'id desc':
                    case 'c_date':
                    case 'c_date asc':
                    case 'c_date desc':
                    case 'e_date':
                    case 'e_date asc':
                    case 'e_date desc':
                        $query['order'] = $params['order'];
                }
            }

            if (isset($params['count'])) {
                unset($query['select']);
                unset($query['limit']);
                unset($query['order']);

                $query['count'] = [
                    'select' => 'id',
                    'as'     => 'count'
                ];

                $query['select'] = ['id', 'parent'];
            }

            $result = QUI::getDataBase()->fetch($query);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        if (isset($params['count'])) {
            return (int)$result[0]['count'];
        }


        // cache db data, so getProduct is faster
        foreach ($result as $entry) {
            $productId = (int)$entry['id'];

            QUI\Cache\LongTermCache::set(
                QUI\ERP\Products\Handler\Cache::getProductCachePath($productId) . '/db-data',
                $entry
            );
        }


        $variants = [];

        foreach ($result as $entry) {
            $productId = (int)$entry['id'];

            try {
                $variants[] = Products::getProduct($productId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        if (!isset($params['limit'])) {
            $this->children = $variants;
        }

        return $variants;
    }

    /**
     * Return a variant children by its variant field hash
     *
     * @param string $hash
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getVariantByVariantHash(string $hash): AbstractType
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id, variantHash',
                'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where'  => [
                    'variantHash' => $hash,
                    'parent'      => $this->getId()
                ],
                'limit'  => 1
            ]);
        } catch (QUI\Exception $Exception) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found.unknown'
                ],
                404,
                ['hash' => $hash]
            );
        }

        if (!isset($result[0])) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found.unknown'
                ],
                404,
                ['hash' => $hash]
            );
        }

        return Products::getProduct($result[0]['id']);
    }

    /**
     * Generate all variants from the given field combinations
     *
     * @param array $fields - list of field values
     *  [
     *      [
     *          fieldId => 1111,
     *          values => [1,2,3]
     *      ],
     *      [
     *          fieldId => 1111,
     *          values => ['valueId','value','value']
     *      ]
     *  ]
     * @param int $generationType
     *
     * @throws QUI\Exception
     */
    public function generateVariants(array $fields = [], int $generationType = self::GENERATION_TYPE_RESET)
    {
        if (empty($fields)) {
            return;
        }

        // delete all children and generate new ones
        if ($generationType === self::GENERATION_TYPE_RESET) {
            $children = $this->getVariants();

            foreach ($children as $Child) {
                try {
                    $Child->delete();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }
        }

        // reset internal cached children
        $this->children = [];

        // generate permutation array
        $list            = [];
        $attributeGroups = [];
        $fieldsParsed    = [];

        $onlyAttributeGroups = true;

        foreach ($fields as $entry) {
            try {
                $Field = FieldHandler::getField($entry['fieldId']);

                if ($Field->getType() !== FieldHandler::TYPE_ATTRIBUTE_LIST) {
                    $onlyAttributeGroups = false;
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
                continue;
            }
        }

        foreach ($fields as $entry) {
            if (empty($entry['fieldId'])) {
                continue;
            }

            $fieldId = (int)$entry['fieldId'];

            if (isset($fieldsParsed[$fieldId])) {
                continue;
            }

            // https://dev.quiqqer.com/quiqqer/products/-/issues/360#note_156320
            if ($onlyAttributeGroups === false) {
                try {
                    $Field = FieldHandler::getField($fieldId);

                    if ($Field->getType() !== FieldHandler::TYPE_ATTRIBUTES) {
                        $attributeGroups[] = [
                            'fieldId' => $Field->getId()
                        ];
                        continue;
                    }

                    if ($Field->getType() !== FieldHandler::TYPE_ATTRIBUTES) {
                        continue;
                    }
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());
                    continue;
                }
            }

            $group  = [];
            $values = $entry['values'];

            foreach ($values as $value) {
                $group[] = ['fieldId' => $fieldId, 'value' => $value];
            }

            if (!empty($group)) {
                $list[] = $group;
            }

            $fieldsParsed[$fieldId] = true;
        }

        $permutations = $this->permutations($list);

        // create variant children
        foreach ($permutations as $permutation) {
            $fields = [];

            foreach ($permutation as $entry) {
                $fields[$entry['fieldId']] = $entry['value'];
            }

            foreach ($attributeGroups as $entry) {
                $fields[$entry['fieldId']] = true;
            }

            if ($generationType === self::GENERATION_TYPE_ADD) {
                // check if variant already exists
                $variantHash = QUI\ERP\Products\Utils\Products::generateVariantHashFromFields($fields);

                try {
                    $this->getVariantByVariantHash($variantHash);
                    continue;
                } catch (QUI\Exception $Exception) {
                    // doesnt exists
                }
            }

            $Variant = $this->generateVariant($fields);

            // workaround
            Products::enableGlobalProductSearchCacheUpdate();
            $Variant->updateCache();
        }
    }

    /**
     * Generate permutation array (all combinations) from a php array list
     *
     * @param array $lists
     * @return array
     */
    protected function permutations(array $lists): array
    {
        $permutations = [];
        $iter         = 0;

        while (true) {
            $num  = $iter++;
            $pick = [];

            foreach ($lists as $l) {
                $r      = $num % count($l);
                $num    = ($num - $r) / count($l);
                $pick[] = $l[$r];
            }

            if ($num > 0) {
                break;
            }

            $permutations[] = $pick;
        }

        return $permutations;
    }

    /**
     * Create a new variant
     *
     * @return VariantChild
     *
     * @throws QUI\Exception
     */
    public function createVariant()
    {
        // set empty url, otherwise we'll have problems.
        $UrlField = $this->getField(FieldHandler::FIELD_URL);
        $UrlField->setValue([]);

        $fields   = [];
        $fields[] = $UrlField;

        $Variant = Products::createProduct(
            $this->getCategories(),
            $fields,
            VariantChild::class,
            $this->getId()
        );

        $this->children[] = $Variant;

        $Variant->setAttribute('parent', $this->getId());
        $Variant->getField(FieldHandler::FIELD_PRODUCT_NO)->setValue('');
        $Variant->getField(FieldHandler::FIELD_FOLDER)->setValue('');
        $Variant->getField(FieldHandler::FIELD_IMAGE)->setValue('');
        $Variant->getField(FieldHandler::FIELD_URL)->setValue([]);

        $Variant->getField(FieldHandler::FIELD_TITLE)->setValue(
            $this->getFieldValue(FieldHandler::FIELD_TITLE)
        );

        $Variant->save();

        return $Variant;
    }

    /**
     * Create a variant by an field array
     * - the url will be adapted to the list fields under certain circumstances
     *
     * @param array $fields
     * @return VariantChild
     *
     * @throws Exception
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function generateVariant(array $fields): VariantChild
    {
        $Variant   = $this->createVariant();
        $fieldList = [];

        // TYPE_ATTRIBUTE_LIST === Auswahllisten
        // TYPE_ATTRIBUTE_GROUPS === Attributlisten

        $onlyAttributeGroups = true; // NUR Attributlisten
        $onlyAttributeLists  = true; // NUR Auswahllisten

        $attributeLists  = [];
        $attributeGroups = [];

        foreach ($fields as $field => $v) {
            try {
                $Field = FieldHandler::getField($field);
                $Field->setValue($v);

                if ($Field->getType() !== FieldHandler::TYPE_ATTRIBUTE_GROUPS) {
                    $onlyAttributeGroups = false;
                }

                if ($Field->getType() === FieldHandler::TYPE_ATTRIBUTE_GROUPS) {
                    $attributeGroups[] = $Field;
                }


                if ($Field->getType() !== FieldHandler::TYPE_ATTRIBUTE_LIST) {
                    $onlyAttributeLists = false;
                }

                if ($Field->getType() === FieldHandler::TYPE_ATTRIBUTE_LIST) {
                    $attributeLists[] = $Field;
                }

                $fieldList[$field] = $Field;
            } catch (QUI\Exception $Exception) {
            }
        }


        // set fields
        foreach ($fieldList as $k => $Field) {
            try {
                $FieldFromParent = $this->getField($Field->getId());

                if ($Field->isUnassigned()) {
                    $Field->setUnassignedStatus(false);
                    $Field->setOwnFieldStatus(true);

                    $evf = $this->getAttribute('editableVariantFields');
                    $ivf = $this->getAttribute('inheritedVariantFields');

                    if (($key = array_search($Field->getId(), $evf)) !== false) {
                        unset($evf[$key]);
                        $this->setAttribute('editableVariantFields', $evf);
                    }

                    if (($key = array_search($Field->getId(), $ivf)) !== false) {
                        unset($ivf[$key]);
                        $this->setAttribute('inheritedVariantFields', $ivf);
                    }

                    $this->save(QUI::getUsers()->getSystemUser());
                }
            } catch (QUI\Exception $Exception) {
                // field is not available, add it
                // and only for AttributeGroup Fields
                if ($Exception->getCode() === 1002
                    && isset($FieldFromParent)
                    && !($FieldFromParent instanceof AttributeGroup)) {
                    $Field->setOwnFieldStatus(true);
                    $this->addField($Field);

                    $evf = $this->getAttribute('editableVariantFields');
                    $ivf = $this->getAttribute('inheritedVariantFields');

                    if (($key = array_search($Field->getId(), $evf)) !== false) {
                        unset($evf[$key]);
                        $this->setAttribute('editableVariantFields', $evf);
                    }

                    if (($key = array_search($Field->getId(), $ivf)) !== false) {
                        unset($ivf[$key]);
                        $this->setAttribute('inheritedVariantFields', $ivf);
                    }


                    $this->save(QUI::getUsers()->getSystemUser());
                    $FieldFromParent = $this->getField($Field->getId());
                } else {
                    QUI\System\Log::writeDebugException($Exception);
                    continue;
                }
            }

            $Field->setOwnFieldStatus($FieldFromParent->isOwnField());

            // Wenn Attributelisten ausgewählt sind
            // Und Auswahllisten ausgewählt sind
            // -> Dann wird die selektierte Auswahllisten den Varianten einfach hinzufügt
            // -> und nicht zum generieren (permutieren) verwendet
            if ($onlyAttributeGroups === false && $onlyAttributeLists === false) {
                // add only attribute groups
                if ($Field->getType() === FieldHandler::TYPE_ATTRIBUTE_GROUPS) {
                    $Variant->addField($Field);

                    try {
                        $Variant->getField($Field->getId())->setUnassignedStatus(false);
                        $Variant->getField($Field->getId())->setValue($fields[$k]);
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::addDebug($Exception->getMessage());
                    }

                    continue;
                }

                if ($Field->getType() === FieldHandler::TYPE_ATTRIBUTE_LIST) {
                    $Variant->addField($Field);
                    $Variant->getField($Field->getId())->setUnassignedStatus(false);
                }

                continue;
            }

            // Wenn keine Attributlisten ausgewählt sind
            // Wenn nur Auswahllisten ausgewählt sind
            /*
                -> dann werden die Einträge in der Ausswahlliste zum generieren (permutieren) verwendet
                -> Auswahhliste wird nicht den Varianten hinzugefügt
                -> Auswahllisten Einträge wird in die URL und den Namen der Variante gesetzt
                -> Preis wird berechnet (Ausgangspreis ist der Preis vom Parent)
                -> Preis ist optional (haken implementieren)
            */
            if ($onlyAttributeLists) {
                $Price = $Variant->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRICE);
                $value = $fields[$k];

                $Field->setValue($value);
                $calc       = $Field->getCalculationData();
                $fieldPrice = QUI\ERP\Money\Price::validatePrice($calc['value']);

                $Price->setValue($Price->getValue() + $fieldPrice);
            }
        }

        // set article no
        $parentProductNo = $this->getFieldValue(FieldHandler::FIELD_PRODUCT_NO);
        $newNumber       = count($this->getVariants()) + 1;

        if (empty($parentProductNo)) {
            $parentProductNo = $this->getId();
        }

        $Variant->getField(FieldHandler::FIELD_PRODUCT_NO)->setValue(
            $parentProductNo . '-' . $newNumber
        );

        // set URL
        if (!QUI::getPackage('quiqqer/products')->getConfig()->get('variants', 'useAttributesForVariantUrl')) {
            $Variant->save();

            return $Variant;
        }

        // use attributes for variant url
        $LocaleClone = clone QUI::getLocale();
        $URL         = $Variant->getField(FieldHandler::FIELD_URL);
        $urlValue    = $URL->getValue();

        if ($onlyAttributeGroups) {
            $attributes = $attributeGroups;
        } elseif ($onlyAttributeLists) {
            $attributes = $attributeLists;
        } else {
            $attributes = $fieldList;
        }

        /* @var $Field QUI\ERP\Products\Field\Field */
        $newValues = [];

        // first add titles to the url

        // second add fields to the url
        foreach ($attributes as $Field) {
            foreach ($urlValue as $lang => $v) {
                $LocaleClone->setCurrent($lang);

                $title = $Field->getTitle($LocaleClone);
                $value = $Field->getValueByLocale($LocaleClone);

                if (empty($value) && !is_numeric($value)) {
                    continue;
                }

                $newValues[$lang][] = QUI\Projects\Site\Utils::clearUrl($title . '-' . $value);
            }
        }

        foreach ($urlValue as $lang => $v) {
            $LocaleClone->setCurrent($lang);
            $productTitle = $this->getTitle($LocaleClone);
            $productTitle = QUI\Projects\Site\Utils::clearUrl($productTitle);

            $productSuffix = '';

            if (!empty($newValues[$lang])) {
                $productSuffix = implode('-', $newValues[$lang]);
                $productSuffix = trim($productSuffix, '-');
            }

            $urlValue[$lang] = $productTitle . '-' . $productSuffix;
        }

        $URL->setValue($urlValue);

        if ($onlyAttributeGroups) {
            $Title = $Variant->getField(FieldHandler::FIELD_TITLE);
            $Title->setValue($urlValue);
        }

        $Variant->save();

        return $Variant;
    }

    /**
     * Validate the fields and return the field data
     * - workaround for validation
     * -> parent variant can have non valid attribute fields and non valid attribute groups.
     * -> the children have to validate them.
     *
     * @return array
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function validateFields(): array
    {
        $fields = $this->getAllProductFields();

        foreach ($fields as $Field) {
            // price fields does not have to be required
            // quiqqer/products#282
            if ($Field instanceof QUI\ERP\Products\Field\Types\Price) {
                $Field->setAttribute('requiredField', false);
            }

            if (!($Field instanceof AttributeGroup)
                && !($Field instanceof ProductAttributeList)) {
                continue;
            }

            try {
                $Field->validate($Field->getValue());
            } catch (QUI\Exception $Exception) {
                // if invalid, use the first option
                $options = $Field->getOptions();

                if (empty($options)) {
                    $Field->setValue($options[0]);
                }
            }
        }

        return parent::validateFields();
    }

    /**
     * return all available fields from the variant children
     * this array contains all field ids and field values that are in use in the children
     *
     * @return array
     */
    public function availableChildFields(): ?array
    {
        if ($this->childFields !== null) {
            return $this->childFields;
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id, parent, fieldData, variantHash',
                'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId()
                ]
            ]);
        } catch (QUI\Exception $Exception) {
            $result = [];
        }

        $this->childFields = $this->parseAvailableFields($result);

        return $this->childFields;
    }

    /**
     * Get all field values of attribute groups and attribute lists of children products that are active
     *
     * @return array
     */
    public function availableActiveChildFields(): ?array
    {
        if ($this->childFieldsActive !== null) {
            return $this->childFieldsActive;
        }

        $this->parseActiveFieldsAndHashes();

        return $this->childFieldsActive;
    }

    /**
     * Get all hashes of attribute groups and attribute lists of children products that are active
     *
     * @return array
     */
    public function availableActiveFieldHashes(): ?array
    {
        if ($this->childFieldHashes !== null) {
            return $this->childFieldHashes;
        }

        $this->parseActiveFieldsAndHashes();

        return $this->childFieldHashes;
    }

    /**
     * Parse the database results from the active fields
     */
    protected function parseActiveFieldsAndHashes()
    {
        $cacheName = QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId()) . '/activeFieldHashes';

        try {
            $fieldHashes = QUI\Cache\LongTermCache::get($cacheName);
        } catch (QUI\Exception $Exception) {
            try {
                $result = QUI::getDataBase()->fetch([
                    'select' => 'id, parent, fieldData, variantHash',
                    'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                    'where'  => [
                        'parent' => $this->getId(),
                        'active' => 1
                    ]
                ]);

                $fieldHashes = $this->parseAvailableFields($result);

                QUI\Cache\LongTermCache::set($cacheName, $fieldHashes);
            } catch (QUI\Exception $Exception) {
                $result      = [];
                $fieldHashes = $this->parseAvailableFields($result);
            }
        }

        $this->childFieldsActive = $fieldHashes['fields'];
        $this->childFieldHashes  = $fieldHashes['hashes'];
    }

    /**
     * @param array $result - all variant children field hashes
     * @return array
     */
    protected function parseAvailableFields(array $result): array
    {
        $fields = [];
        $hashes = [];

        foreach ($result as $entry) {
            $fieldData     = json_decode($entry['fieldData'], true);
            $variantFields = [];

            if (!is_array($fieldData)) {
                $fieldData = [];
            }

            foreach ($fieldData as $field) {
                $variantFields[$field['id']] = $field['value'];
            }

            $variantHash = $entry['variantHash'];
            $hashes[]    = $variantHash;

            $variantHash = trim($variantHash, ';');
            $variantHash = explode(';', $variantHash);

            foreach ($variantHash as $fieldHash) {
                $fieldHash = explode(':', $fieldHash);
                $fieldId   = (int)$fieldHash[0];

                if (isset($variantFields[$fieldId])) {
                    $fields[$fieldId][] = $variantFields[$fieldId];
                    $fields[$fieldId]   = array_unique($fields[$fieldId]);
                }
            }
        }

        return [
            'fields' => $fields,
            'hashes' => $hashes
        ];
    }

    /**
     * Return if the field is selectable / available
     * It will be checked if this field is present in a children
     *
     * @param string|int $fieldId
     * @param string|int $fieldValue
     *
     * @return bool
     */
    public function isFieldAvailable($fieldId, $fieldValue): bool
    {
        $available = $this->availableChildFields();

        if (!isset($available[$fieldId])) {
            return false;
        }

        $fields = $available[$fieldId];

        foreach ($fields as $value) {
            if ($value == $fieldValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return true if this product has the product id as variant
     *
     * @param integer $variantId - ID of the product / variant
     * @return bool
     */
    public function hasVariantId(int $variantId): bool
    {
        $variantId = (int)$variantId;
        $variants  = $this->getVariants();

        foreach ($variants as $Variant) {
            if ($variantId === $Variant->getId()) {
                return true;
            }
        }

        return false;
    }

    //region default variant

    /**
     * Set the default variant
     * - checks if variant id is a variant product of this product
     * - if the product is no variant product, then the id will not be set as default variant id
     *
     * @param integer $variantId - ID of the product / variant
     */
    public function setDefaultVariant(int $variantId)
    {
        if ($this->hasVariantId($variantId) === false) {
            return;
        }

        $this->setAttribute('defaultVariantId', $variantId);
    }

    /**
     * Unset the default variant
     * - no variant is the default variant anymore
     */
    public function unsetDefaultVariant()
    {
        $this->setAttribute('defaultVariantId', null);
    }

    /**
     * Return the default variant child
     *
     * @throws Exception
     */
    public function getDefaultVariant()
    {
        $variantId = $this->getAttribute('defaultVariantId');

        if (!$variantId) {
            throw new QUI\ERP\Products\Product\Exception();
        }

        $Product = Products::getProduct($variantId);

        // set attribute lists
        $attributeLists = $this->getFieldsByType(FieldHandler::TYPE_ATTRIBUTE_LIST);

        foreach ($attributeLists as $Field) {
            try {
                $Product->getField($Field->getId())->setValue($Field->getValue());
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $Product;
    }

    /**
     * Return the default variant id
     *
     * @return false|integer
     */
    public function getDefaultVariantId()
    {
        $variantId = $this->getAttribute('defaultVariantId');

        if (!empty($variantId)) {
            return (int)$variantId;
        }

        return false;
    }

    //endregion
}
