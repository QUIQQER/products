<?php

/**
 * This file contains QUI\ERP\Products\Product\Model
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Search as SearchHandler;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Product\Cache\ProductCache;
use QUI\ERP\Products\Utils\Products as ProductUtils;
use QUI\Projects\Media\Utils as MediaUtils;

use function array_filter;
use function array_flip;
use function array_key_first;
use function array_keys;
use function array_merge;
use function array_reverse;
use function array_unique;
use function array_values;
use function ceil;
use function count;
use function date;
use function defined;
use function explode;
use function floor;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function md5;
use function round;
use function trim;
use function urlencode;

/**
 * Class Controller
 * Product Model
 *
 * This class is the main data object for a product
 * This class handles all data from and for a product
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 *
 * Exceptions:
 * - Code 404 (QUI\ERP\Products\Product\Exception) Product not found
 * - Code 1002 (QUI\ERP\Products\Product\Exception) Field not found
 * - Code 1003 (QUI\ERP\Products\Product\Exception) Field is invalid
 * - Code 1004 (QUI\ERP\Products\Product\Exception) Field is empty but required
 *
 * permission.viewable
 * permission.buyable
 */
class Model extends QUI\QDOM
{
    /**
     * Product-ID
     * @var
     */
    protected $id;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * Permissions list
     * @var array
     */
    protected $permissions = [];

    /**
     * @var null
     */
    protected $Category = null;

    /**
     * @var QUI\ERP\Currency\Currency
     */
    protected $Currency = null;

    /**
     * Activate / Deactivate status
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Force the application of all price factors on product save.
     * This includes price fields that are normally not updated on product save.
     *
     * This is a special flag for the price field factor feature.
     *
     * @var bool
     */
    protected $forcePriceFactorUse = false;

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
        if (empty($product)) {
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

        $this->id = (int)$pid;
        $this->active = (int)$product['active'] ? true : false;

        if (!empty($product['permissions']) && $product['permissions'] !== '[]') {
            $this->permissions = json_decode($product['permissions'], true);
        }

        // view permissions prüfung wird im Frontend view gemacht (ViewFrontend)

        unset($product['id']);
        unset($product['active']);

        $this->setAttributes($product);

        // categories
        $categories = explode(',', trim($product['categories'], ','));

        if (is_array($categories)) {
            foreach ($categories as $categoryId) {
                try {
                    $Category = QUI\ERP\Products\Handler\Categories::getCategory($categoryId);

                    $this->categories[$Category->getId()] = $Category;

                    /** @var QUI\ERP\Products\Field\Field $CategoryField */
                    foreach ($Category->getFields() as $CategoryField) {
                        $this->fields[$CategoryField->getId()] = clone $CategoryField;
                    }
                } catch (QUI\Exception $Exception) {
                }
            }
        }

        if (!isset($this->categories[0])) {
            $this->categories[0] = QUI\ERP\Products\Handler\Categories::getCategory(0);
        }


        // main category
        $mainCategory = $this->getAttribute('category');

        if ($mainCategory !== false && isset($this->categories[$mainCategory])) {
            try {
                $this->Category = Categories::getCategory($mainCategory);
            } catch (QUI\Exception $Exception) {
            }
        }

        if (!$this->Category) {
            $this->Category = $this->categories[0];
        }


        // fields
        $fields = json_decode($product['fieldData'], true);

        if (!is_array($fields)) {
            $fields = [];
        }

        foreach ($fields as $field) {
            if (!isset($field['id']) && !isset($field['value'])) {
                continue;
            }

            try {
                $Field = Fields::getField($field['id']);
                $Field->setProduct($this);

                $this->fields[$Field->getId()] = $Field;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_DEBUG);
                continue;
            }

            if (isset($field['unassigned'])) {
                $Field->setUnassignedStatus($field['unassigned']);
            }

            if (isset($field['ownField'])) {
                $Field->setOwnFieldStatus($field['ownField']);
            }

            if (isset($field['isPublic'])) {
                $Field->setPublicStatus((bool)$field['isPublic']);
            }

            if ($Field instanceof QUI\ERP\Products\Field\Types\Price && !empty($field['value'])) {
                $field['value'] = $Field->cleanup($field['value']);
            }

            try {
                $Field->setValue($field['value']);
//            } catch (QUI\ERP\Products\Field\ExceptionRequired $Exception) {
//                throw $Exception;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_DEBUG);
            }
        }

        // all standard and all system fields must be in the product
        $systemFields = Fields::getFields([
            'where_or' => [
                'systemField' => 1,
                'standardField' => 1
            ]
        ]);

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($systemFields as $Field) {
            if (!isset($this->fields[$Field->getId()])) {
                $this->fields[$Field->getId()] = $Field;
            }
        }

        // editable Variant Fields
        if (!empty($product['editableVariantFields']) && is_string($product['editableVariantFields'])) {
            $this->setAttribute(
                'editableVariantFields',
                json_decode($product['editableVariantFields'], true)
            );
        } else {
            $this->setAttribute('editableVariantFields', false);
        }

        if (!empty($product['inheritedVariantFields']) && is_string($product['inheritedVariantFields'])) {
            $this->setAttribute(
                'inheritedVariantFields',
                json_decode($product['inheritedVariantFields'], true)
            );
        } else {
            $this->setAttribute('inheritedVariantFields', false);
        }

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }

        if (
            $this instanceof QUI\ERP\Products\Product\Types\VariantParent ||
            $this instanceof QUI\ERP\Products\Product\Types\VariantChild
        ) {
            $attributeList = $this->getFieldsByType(Fields::TYPE_ATTRIBUTE_GROUPS);
            $Field = null;

            if (empty($attributeList)) {
                $Field = Fields::getField(Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES);

                $this->fields[$Field->getId()] = clone $Field;
            } elseif (count($attributeList) === 1) {
                $Field = $attributeList[0];

                if ($Field->getId() === Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES) {
                    $Field = Fields::getField(Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES);

                    $this->fields[$Field->getId()] = clone $Field;
                }
            }

            if (isset($this->fields[Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES])) {
                $Field = $this->fields[Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES];
                $Field->setPublicStatus(true);
                $Field->setOwnFieldStatus(true);
            }
        }

        foreach ($this->fields as $Field) {
            $Field->setProduct($this);
        }
    }

    /**
     * Return the duly view
     *
     * @return ViewFrontend|ViewBackend
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getView()
    {
        switch ($this->getAttribute('viewType')) {
            case 'backend':
                return $this->getViewBackend();

            default:
                return $this->getViewFrontend();
        }
    }

    /**
     * @return ViewFrontend
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getViewFrontend()
    {
        return new ViewFrontend($this);
    }

    /**
     * @return ViewBackend
     */
    public function getViewBackend()
    {
        return new ViewBackend($this);
    }

    /**
     * Return the product as unique product
     *
     * @param QUI\Interfaces\Users\User|null $User
     * @return UniqueProduct
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function createUniqueProduct($User = null)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        // $Locale = $User->getLocale(); // quiqqer/order#158
        $Locale = QUI\ERP\Products\Handler\Products::getLocale();

        $fieldList = $this->getFields();
        $attributes = null;

        if (Products::$useRuntimeCacheForUniqueProducts) {
            $cacheName = self::getUniqueProductCachePath($User);
            $attributes = ProductCache::getUniqueProductData($cacheName);
        }

        if (!$attributes) {
            $attributes = $this->getAttributes();
            $attributes['title'] = $this->getTitle($Locale);
            $attributes['description'] = $this->getDescription($Locale);
            $attributes['uid'] = $User->getId();
            $attributes['displayPrice'] = true;
            $attributes['maximumQuantity'] = $this->getMaximumQuantity();

            $fields = [];

            foreach ($fieldList as $Field) {
                /* @var $Field QUI\ERP\Products\Field\CustomCalcField */
                if ($Field instanceof QUI\ERP\Products\Field\CustomCalcField) {
                    $calcData['custom_calc'] = $Field->getCalculationData($Locale);

                    $fields[] = array_merge(
                        $Field->toProductArray(),
                        $Field->getAttributes(),
                        $calcData
                    );

                    continue;
                }

                /* @var $Field QUI\ERP\Products\Field\Field */
                $fields[] = array_merge(
                    $Field->toProductArray(),
                    $Field->getAttributes()
                );
            }

            if (!empty($fields)) {
                $attributes['fields'] = $fields;
            }
        }

        if (Products::$useRuntimeCacheForUniqueProducts) {
            ProductCache::writeUniqueProductData($attributes, $cacheName);
        }

        QUI::getEvents()->fireEvent('quiqqerProductsToUniqueProduct', [$this, &$attributes]);

        return new UniqueProduct($this->getId(), $attributes);
    }

    /**
     * Clear cache for unique version of this product of $User
     *
     * @param QUI\Interfaces\Users\User $User
     * @return void
     */
    public function clearUniqueProductCache(QUI\Interfaces\Users\User $User): void
    {
        ProductCache::clearUniqueProductDataCache(self::getUniqueProductCachePath($User));
    }

    /**
     * Get cache path for the unique version of this product for $User
     *
     * @param QUI\Interfaces\Users\User $User
     * @return string
     */
    protected function getUniqueProductCachePath(QUI\Interfaces\Users\User $User)
    {
        // $Locale = $User->getLocale(); // quiqqer/order#158
        $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        $fieldList = $this->getFields();
        $cacheName = QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId()) . '/';

        $uniqueCacheParts = [
            $Locale->getCurrent(),
            $User->getId()
        ];

        foreach ($fieldList as $Field) {
            $uniqueCacheParts[] = json_encode($Field->toProductArray());
        }

        return $cacheName . md5(implode('_', $uniqueCacheParts));
    }

    /**
     * Create the media folder for the product
     * if the product has a folder, no folder would be created
     *
     * @param integer|boolean $fieldId - optional, Media Folder Field id,
     *                                   if you want to create a media folder for a media folder field
     * @return QUI\Projects\Media\Folder
     *
     * @throws QUI\Exception
     */
    public function createMediaFolder($fieldId = false)
    {
        // create field folder
        if ($fieldId) {
            $Field = $this->getField($fieldId);

            if ($Field->getType() != Fields::TYPE_FOLDER) {
                throw new QUI\ERP\Products\Product\Exception([
                    'quiqqer/products',
                    'exception.product.field.is.no.media.folder'
                ]);
            }

            // exist a media folder in the field?
            try {
                $folderUrl = $this->getFieldValue($fieldId);
                $Folder = MediaUtils::getMediaItemByUrl($folderUrl);

                if (MediaUtils::isFolder($Folder)) {
                    /* @var $Folder QUI\Projects\Media\Folder */
                    return $Folder;
                }
            } catch (QUI\Exception $Exception) {
            }

            $MainFolder = $this->createMediaFolder();

            try {
                if ($MainFolder->childWithNameExists($fieldId)) {
                    $Folder = $MainFolder->getChildByName($fieldId);
                } else {
                    $Folder = $MainFolder->createFolder($fieldId);
                    $Folder->setAttribute('order', 'priority ASC');
                    $Folder->save();
                }
            } catch (QUI\Exception $Exception) {
                if ($Exception->getCode() != 701) {
                    throw $Exception;
                }

                $Folder = $MainFolder->getChildByName($fieldId);
            }

            $Field = $this->getField($fieldId);
            $Field->setValue($Folder->getUrl());
            $this->update();

            return $Folder;
        }

        // create main media folder
        try {
            return $this->getMediaFolder();
        } catch (QUI\Exception $Exception) {
        }

        // create folder
        $Parent = Products::getParentMediaFolder();

        try {
            $productId = $this->getId();

            if ($Parent->childWithNameExists($productId)) {
                $Folder = $Parent->getChildByName($productId);
            } else {
                $Folder = $Parent->createFolder($this->getId());
                $Folder->setAttribute('order', 'priority ASC');
                $Folder->save();
            }
        } catch (QUI\Exception $Exception) {
            if ($Exception->getCode() != 701) {
                throw $Exception;
            }

            $Folder = $Parent->getChildByName($this->getId());
        }

        $Field = $this->getField(Fields::FIELD_FOLDER);
        $Field->setValue($Folder->getUrl());

        $this->update();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCreateMediaFolder', [$this]);

        return $Folder;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the product priority
     *
     * @return int
     *
     * @throws Exception
     */
    public function getPriority()
    {
        return $this->getFieldValue(Fields::FIELD_PRIORITY);
    }

    /**
     * Return the priority field object
     *
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws Exception
     */
    public function getPriorityField()
    {
        return $this->getField(Fields::FIELD_PRIORITY);
    }

    /**
     * Return the URL for the product
     * It uses the current project
     *
     * @param QUI\Projects\Project|null $Project
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrl($Project = null)
    {
        if ($Project === null) {
            $Project = QUI::getRewrite()->getProject();
        }

        $cacheName = QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId());
        $cacheName .= '/url';
        $cacheName .= '/' . $Project->getName();
        $cacheName .= '/' . $Project->getLang();

        try {
            $url = QUI\Cache\LongTermCache::get($cacheName);
            $url = \parse_url($url, PHP_URL_PATH);

            return $url;
        } catch (QUI\Exception $Exception) {
        }

        // look if category is in product and it is the correct site
        $Category = $this->getCategory();
        $sites = $Category->getSites($Project);

        $checkSitePath = function ($list) {
            foreach ($list as $Site) {
                $catId = $Site->getAttribute('quiqqer.products.settings.categoryId');
                $type = $Site->getAttribute('type');

                if ($type !== 'quiqqer/products:types/category') {
                    return true;
                }

                if ($catId === false) {
                    return false;
                }

                if (!isset($this->categories[$catId])) {
                    return false;
                }
            }

            return true;
        };

        foreach ($sites as $CategorySite) {
            $list = $CategorySite->getParents();
            $list[] = $CategorySite;
            $list = array_reverse($list);

            if ($checkSitePath($list)) {
                $Site = $CategorySite;
                break;
            }
        }

        if (!isset($Site) && isset($sites[0])) {
            $Site = $sites[0];
        }

        if (
            !isset($Site)
            || $Site->getAttribute('quiqqer.products.fake.type')
            || $Site->getAttribute('type') !== 'quiqqer/products:types/category'
            && $Site->getAttribute('type') !== 'quiqqer/products:types/search'
        ) {
            QUI\System\Log::addWarning(
                QUI::getLocale()->get('quiqqer/products', 'exception.product.url.missing', [
                    'productId' => $this->getId(),
                    'title' => $this->getTitle()
                ])
            );

            return '/_p/' . $this->getUrlName();
        }

        try {
            $url = $Site->getUrlRewritten([
                0 => $this->getUrlName(),
                'paramAsSites' => true
            ]);
        } catch (\Exception $Exception) {
            return '/_p/' . $this->getUrlName();
        }

        QUI\Cache\LongTermCache::set($cacheName, $url);

        return $url;
    }

    /**
     * @param null $Project
     * @return string
     * @throws QUI\Exception
     */
    public function getUrlRewrittenWithHost($Project = null)
    {
        if (!$Project) {
            $Project = QUI::getRewrite()->getProject();
        }

        $Category = $this->getCategory();
        $Site = $Category->getSite($Project);

        if (
            !$Site->getAttribute('active')
            || $Site->getAttribute('quiqqer.products.fake.type')
            || $Site->getAttribute('type') !== 'quiqqer/products:types/category'
            && $Site->getAttribute('type') !== 'quiqqer/products:types/search'
        ) {
            QUI\System\Log::addInfo(
                QUI::getLocale()->get('quiqqer/products', 'exception.product.url.missing', [
                    'productId' => $this->getId(),
                    'title' => $this->getTitle()
                ]),
                [
                    'wantedLanguage' => $Project->getLang(),
                    'wantedProject' => $Project->getName()
                ]
            );

            return $Project->getVHost(true, true) . '/_p/' . $this->getUrlName();
        }

        try {
            return $Site->getUrlRewrittenWithHost([
                0 => $this->getUrlName(),
                'paramAsSites' => true
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo(
                QUI::getLocale()->get('quiqqer/products', 'exception.product.url.missing', [
                    'productId' => $this->getId(),
                    'title' => $this->getTitle()
                ]),
                [
                    'wantedLanguage' => $Project->getLang(),
                    'wantedProject' => $Project->getName()
                ]
            );

            return $Project->getVHost(true, true) . '/_p/' . $this->getUrlName();
        }
    }

    /**
     * Return name for rewrite url
     *
     * @return string
     */
    public function getUrlName()
    {
        $url = '';
        $useUrlField = false;

        try {
            $Field = $this->getField(Fields::FIELD_URL);
            $url = $Field->getValueByLocale();
            $useUrlField = true;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        if (empty($url)) {
            $useUrlField = false;
            $url = QUI\Projects\Site\Utils::clearUrl($this->getTitle());
        }

        $parts = [$url];

        if ($useUrlField === false) {
            $parts[] = $this->getId();
        }

        return urlencode(implode(QUI\Rewrite::URL_PARAM_SEPARATOR, $parts));
    }

    /**
     * Return the title of the product
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getTitle($Locale = null)
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_TITLE, $Locale);

        if ($result) {
            return $result;
        }

        QUI\System\Log::addWarning(
            QUI::getLocale()->get(
                'quiqqer/products',
                'warning.product.have.no.title',
                ['id' => $this->getId()]
            ),
            [
                'id' => $this->getId()
            ]
        );

        return '';
    }

    /**
     * Return the description of the product
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getDescription($Locale = null)
    {
        $result = $this->getLanguageFieldValue(
            Fields::FIELD_SHORT_DESC,
            $Locale
        );

        if ($result) {
            return $result;
        }

        return '';
    }

    /**
     * Return the product content
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getContent($Locale = null)
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_CONTENT, $Locale);

        if ($result) {
            return $result;
        }

        return '';
    }

    /**
     * Return the value of an language field
     *
     * @param integer $field - optional
     * @param QUI\Locale|null $Locale - optional
     *
     * @return string|boolean
     */
    protected function getLanguageFieldValue($field, $Locale = null)
    {
        if (!$Locale) {
            $Locale = Products::getLocale();
        }

        $current = $Locale->getCurrent();

        try {
            $Field = $this->getField($field);
            $data = $Field->getValue();

            if (empty($data)) {
                return false;
            }

            if (is_string($data)) {
                return $data;
            }

            if (isset($data[$current]) && !empty($data[$current])) {
                return $data[$current];
            }

            // search none empty
            foreach ($data as $lang => $value) {
                if (!empty($value)) {
                    return $value;
                }
            }

            if (isset($data[$current])) {
                return $data[$current];
            }
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Return the price of the product
     *
     * Observes all price fields and searches for the correct price field at this time.
     *
     * @param null|QUI\Interfaces\Users\User $User - optional, default = Nobody
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getPrice($User = null)
    {
        return ProductUtils::getPriceFieldForProduct($this, $User);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function hasOfferPrice()
    {
        $OfferPrice = $this->getField(Fields::FIELD_PRICE_OFFER);

        if (!$OfferPrice) {
            return false;
        }

        $value = $OfferPrice->getValue();

        if ($value === false) {
            return false;
        }

        if ($value === null) {
            return false;
        }

        return $value !== '';
    }

    /**
     * @return false|QUI\ERP\Products\Field\UniqueField
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getOriginalPrice()
    {
        return $this->createUniqueProduct()->getOriginalPrice();
    }

    /**
     * Return a calculated price field
     *
     * @param $FieldId
     *
     * @return false|QUI\ERP\Products\Field\UniqueField
     *
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getCalculatedPrice($FieldId)
    {
        return $this->createUniqueProduct()->getCalculatedPrice($FieldId);
    }

    /**
     * Alias for getPrice
     * So, the Product has the same construction as the UniqueProduct
     *
     * @param null|QUI\Interfaces\Users\User $User
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getNettoPrice($User = null)
    {
        return $this->getPrice($User);
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
        return $this->getMinimumPrice($User);
    }

    /**
     * Return the minimum price
     *
     * @param null $User
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     *
     * @todo we have maybe a bug here; in theory all field combinations would have to be tested
     */
    public function getMinimumPrice($User = null)
    {
        $baseCacheName = QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId());
        $cacheName = $baseCacheName . '/prices/min';

        if ($User && $User instanceof QUI\Interfaces\Users\User && !QUI::getUsers()->isNobodyUser($User)) {
            $cacheName = $baseCacheName . '/prices/' . $User->getId() . '/min';
        }

        try {
            $data = QUI\Cache\LongTermCache::get($cacheName);
            $Currency = QUI\ERP\Currency\Handler::getCurrency($data['currency']);

            return new QUI\ERP\Money\Price($data['price'], $Currency, $User);
        } catch (QUI\Exception $Exception) {
        }

        // search all custom fields, and set the minimum
        $Clone = Products::getNewProductInstance($this->getId());
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);
        $UniqueProduct = $Clone->createUniqueProduct($User);
        $UniqueProduct->calc($Calc);

        $uniqueProductAttributes = $UniqueProduct->getAttributes();

        $Price = $UniqueProduct->getPrice();
        $currentPrice = $uniqueProductAttributes['price_netto'];

        if (
            QUI::getPackage('quiqqer/products')
                ->getConfig()
                ->get('products', 'useAttributeListsForMinMaxPriceCalculation')
        ) {
            $fields = $Clone->getFieldsByType([
                Fields::TYPE_ATTRIBUTE_LIST
            ]);

            // alle felder müssen erst einmal gesetzt werden
            /* @var $Field QUI\ERP\Products\Field\Field */
            foreach ($fields as $Field) {
                if (!($Field instanceof QUI\ERP\Products\Field\CustomCalcField)) {
                    continue;
                }

                $options = $Field->getOptions();

                if (count($options['entries'])) {
                    $Clone->getField($Field->getId())->setValue(0);
                }
            }

            /* @var $Field QUI\ERP\Products\Field\Field */
            foreach ($fields as $Field) {
                if (!($Field instanceof QUI\ERP\Products\Field\CustomCalcField)) {
                    continue;
                }

                $options = $Field->getOptions();

                foreach ($options['entries'] as $index => $data) {
                    $Clone->getField($Field->getId())->setValue($index);

                    $price = $Clone->createUniqueProduct($User)->calc($Calc)->getPrice()->value();

                    if ($currentPrice > $price) {
                        $currentPrice = $price;
                    }
                }
            }
        }

        $Result = new QUI\ERP\Money\Price($currentPrice, $Price->getCurrency(), $User);

        try {
            QUI\Cache\LongTermCache::set($cacheName, $Result->toArray());
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $Result;
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
        $baseCacheName = QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId());
        $cacheName = $baseCacheName . '/prices/max';

        if ($User && $User instanceof QUI\Interfaces\Users\User && !QUI::getUsers()->isNobodyUser($User)) {
            $cacheName = $baseCacheName . '/prices/' . $User->getId() . '/max';
        }

        try {
            $data = QUI\Cache\LongTermCache::get($cacheName);
            $Currency = QUI\ERP\Currency\Handler::getCurrency($data['currency']);

            return new QUI\ERP\Money\Price($data['price'], $Currency, $User);
        } catch (QUI\Exception $Exception) {
        }

        $Clone = Products::getNewProductInstance($this->getId());
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);
        $UniqueProduct = $Clone->createUniqueProduct($User);
        $UniqueProduct->calc($Calc);

        $uniqueProductAttributes = $UniqueProduct->getAttributes();

        $Price = $UniqueProduct->getPrice();
        $currentPrice = $uniqueProductAttributes['price_netto'];

        if (
            QUI::getPackage('quiqqer/products')
                ->getConfig()
                ->get('products', 'useAttributeListsForMinMaxPriceCalculation')
        ) {
            $fields = $Clone->getFieldsByType([
                Fields::TYPE_ATTRIBUTE_LIST
            ]);

            /* @var $Field QUI\ERP\Products\Field\Field */
            foreach ($fields as $Field) {
                if (!($Field instanceof QUI\ERP\Products\Field\CustomCalcField)) {
                    continue;
                }

                $options = $Field->getOptions();

                foreach ($options['entries'] as $index => $data) {
                    $Clone->getField($Field->getId())->setValue($index);

                    $price = $Clone->createUniqueProduct($User)->calc($Calc)->getPrice()->value();

                    if ($currentPrice < $price) {
                        $currentPrice = $price;
                    }
                }
            }
        }

        $Result = new QUI\ERP\Money\Price($currentPrice, $Price->getCurrency(), $User);

        QUI\Cache\LongTermCache::set($cacheName, $Result->toArray());

        return $Result;
    }

    /**
     * Return the maximum quantity for this product
     *
     * @return bool|integer|float
     */
    public function getMaximumQuantity()
    {
        $quantity = true;

        try {
            QUI::getEvents()->fireEvent('onQuiqqerProductsProductGetMaxQuantity', [$this, &$quantity]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        return $quantity;
    }

    /**
     * Return the attributes
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['id'] = $this->getId();
        $attributes['active'] = $this->isActive();
        $attributes['title'] = $this->getTitle();
        $attributes['description'] = $this->getDescription();
        $attributes['permissions'] = $this->getPermissions();
        $attributes['image'] = false;

        try {
            $attributes['image'] = $this->getImage()->getUrl(true);
        } catch (QUI\Exception $Exception) {
        }


        /* @var $Price QUI\ERP\Money\Price */
        $Price = $this->getPrice();

        $attributes['price_netto'] = $Price->value();
        $attributes['price_currency'] = $Price->getCurrency()->getCode();

        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        }

        // fields
        $fields = [];
        $fieldList = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fieldList as $Field) {
            $field = array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );

            $field['isPriceField'] = $Field instanceof QUI\ERP\Products\Field\Types\Price;

            $fields[] = $field;
        }

        if (!empty($fields)) {
            $attributes['fields'] = $fields;
        }

        // categories
        $categories = [];
        $catList = $this->getCategories();

        /* @var $Category Category */
        foreach ($catList as $Category) {
            $categories[] = $Category->getId();
        }

        if (!empty($categories)) {
            $attributes['categories'] = implode(',', $categories);
        }

        return $attributes;
    }

    /**
     * Alias for save()
     *
     * @param QUI\Interfaces\Users\User $EditUser (optional) - The user that executes the operation
     * @return void
     *
     * @throws QUI\Exception
     */
    public function update($EditUser = null)
    {
        $this->save($EditUser);
    }

    /**
     * save / update the product data
     *
     * @param QUI\Interfaces\Users\User $EditUser (optional) - The user that executes the operation
     * @return void
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     */
    public function save($EditUser = null)
    {
        $this->productSave($this->getFieldData(), $EditUser);
    }

    /**
     * Internal saving method
     *
     * @param array $fieldData - field data
     * @param QUI\Interfaces\Users\User $EditUser (optional) - The user that executes the operation
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     * @throws Exception
     */
    protected function productSave($fieldData, $EditUser = null)
    {
        if (empty($EditUser)) {
            $EditUser = QUI::getUserBySession();
        }

        QUI\Permissions\Permission::checkPermission('product.edit', $EditUser);

        if (Products::$fireEventsOnProductSave) {
            QUI::getEvents()->fireEvent('onQuiqqerProductsProductSaveBefore', [&$fieldData, $this]);
        }

        // cleanup fields
        foreach ($fieldData as $key => $field) {
            if ($field['id'] < 1000) {
                continue;
            }

            if ($field['ownField']) {
                continue;
            }

            $Field = Fields::getField($field['id']);

            if ($Field->isSystem()) {
                continue;
            }


            $categories = $this->getCategories();
            $catHasField = false;

            /* @var $Category Category */
            foreach ($categories as $Category) {
                $CatField = $Category->getField($Field->getId());

                if ($CatField) {
                    $catHasField = true;
                    break;
                }
            }

            if (!$catHasField) {
                $field['unassigned'] = true;
            }
        }

        // cleanup urls
        $urlField = array_filter($fieldData, function ($field) {
            return $field['id'] === Fields::FIELD_URL;
        });

        $urlKey = array_key_first($urlField);
        $urlField = array_values($urlField);
        $urls = [];

        if (isset($urlField[0])) {
            $urls = $urlField[0]['value'];
        }

        if (empty($urls)) {
            $urls = [];
        }

        foreach ($urls as $lang => $url) {
            if (empty($url)) {
                continue;
            }

            $urls[$lang] = QUI\Projects\Site\Utils::clearUrl($url);
        }

        $fieldData[$urlKey]['value'] = $urls;
        $this->getField(Fields::FIELD_URL)->setValue($urls);

        // Check if article no. is unique
        if ($this->isActive()) {
            foreach ($fieldData as $field) {
                if ($field['id'] !== Fields::FIELD_PRODUCT_NO) {
                    continue;
                }

                $articleNo = $field['value'];

                if (empty($articleNo)) {
                    break;
                }

                $this->checkDuplicateArticleNo($articleNo);
            }
        }

        // if variant child
        // only save non inherited fields
        if ($this instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $inheritedFields = ProductUtils::getInheritedFieldIdsForProduct($this);
            $inheritedFields = array_flip($inheritedFields);

            $editableFields = ProductUtils::getEditableFieldIdsForProduct($this);
            $editableFields = array_flip($editableFields);

            $fieldData = array_filter($fieldData, function ($field) use ($inheritedFields, $editableFields) {
                $fieldId = $field['id'];
                $Field = Fields::getField($fieldId);

                if ($Field->getType() === Fields::TYPE_ATTRIBUTE_LIST) {
                    return true;
                }

                if ($Field->getType() === Fields::TYPE_ATTRIBUTE_GROUPS) {
                    return true;
                }

                return !isset($inheritedFields[$fieldId]) || isset($editableFields[$fieldId]);
            });
        }

        // check url
        $this->checkProductUrl($fieldData);

        $categoryIds = array_keys($this->categories);

        /* @var $Field FieldInterface */

        // set main category
        $mainCategory = '';
        $Category = $this->getCategory();

        if ($Category) {
            $mainCategory = $Category->getId();
        }

        $this->setAttribute('e_date', date('Y-m-d H:i:s'));

        $parentId = (int)$this->getAttribute('parent');

        if (empty($parentId)) {
            $parentId = null;
        }

        // update
        if (Products::$writeProductDataToDb) {
            QUI\Watcher::addString(
                QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.save', [
                    'id' => $this->getId()
                ]),
                '',
                [
                    'categories' => ',' . implode(',', $categoryIds) . ',',
                    'category' => $mainCategory,
                    'fieldData' => json_encode($fieldData),
                    'permissions' => json_encode($this->permissions),
                    'priority' => $this->getPriority()
                ]
            );

            QUI::getDataBase()->update(
                QUI\ERP\Products\Utils\Tables::getProductTableName(),
                [
                    'parent' => $parentId,
                    'categories' => ',' . implode(',', $categoryIds) . ',',
                    'category' => $mainCategory,
                    'fieldData' => json_encode($fieldData),
                    'permissions' => json_encode($this->permissions),
                    'e_user' => $EditUser->getId(),
                    'e_date' => $this->getAttribute('e_date')
                ],
                ['id' => $this->getId()]
            );

            $this->updateCache();
        }

        QUI\Cache\LongTermCache::clear(
            QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId())
        );

        QUI\ERP\Products\Handler\Cache::clearProductFrontendCache($this->getId());

        Products::cleanProductInstanceMemCache($this->getId());

        if (Products::$fireEventsOnProductSave) {
            QUI::getEvents()->fireEvent('onQuiqqerProductsProductSave', [$this]);
        }

        $this->buildCache();
    }

    /**
     * Build the mem cache for the product (not the db table cache)
     * its the faster cache
     */
    public function buildCache()
    {
        try {
            // cache db attributes
            $result = QUI::getDataBase()->fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where' => [
                    'id' => $this->getId()
                ],
                'limit' => 1
            ]);

            if (!empty($result)) {
                QUI\Cache\LongTermCache::set(
                    QUI\ERP\Products\Handler\Cache::getProductCachePath($this->getId()) . '/db-data',
                    $result[0]
                );
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }
    }

    /**
     * Check if the product url already exists in the category
     *
     * @param array $fieldData
     * @throws Exception
     */
    protected function checkProductUrl($fieldData)
    {
        // check url
        $urlField = array_filter($fieldData, function ($field) {
            return $field['id'] === Fields::FIELD_URL;
        });

        $urlField = array_values($urlField);
        $urls = [];

        if (isset($urlField[0])) {
            $urls = $urlField[0]['value'];
        }

        ProductUtils::checkUrlByUrlFieldValue(
            $urls,
            $this->getCategory()->getId(),
            $this->getId()
        );
    }

    /**
     * save / update the product data
     * and check the product fields if the product is active
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function userSave()
    {
        if ($this->isActive()) {
            $fieldData = $this->validateFields();
        } else {
            $fieldData = $this->getFieldData();
        }

        $this->productSave($fieldData);

        if (Products::$fireEventsOnProductSave) {
            QUI::getEvents()->fireEvent('onQuiqqerProductsProductUserSave', [$this]);
        }
    }

    /**
     * Validate the fields and return the field data
     *
     * @return array
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function validateFields()
    {
        // Update price fields by factors
        $this->updateProductPricesByFactors();

        $fieldData = [];
        $fields = $this->getAllProductFields();

        // generate the product field data
        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            $value = $Field->getValue();

            $this->setUnassignedStatusToField($Field);

            if ($Field->isUnassigned()) {
                continue;
            }

            if (!$Field->isRequired() || $Field->isCustomField()) {
                $Field->validate($value);

                $fieldData[] = $Field->toProductArray();
                continue;
            }

            try {
                // if field is a price field and the product is a variant parent
                // price may remain empty for the parent,
                // since the variant children have prices and the parent cannot be ordered
                if (
                    $this instanceof QUI\ERP\Products\Product\Types\VariantParent &&
                    $Field->getId() === Fields::FIELD_PRICE
                ) {
                    continue;
                }

                $Field->validate($value);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning(
                    $Exception->getMessage(),
                    [
                        'id' => $Field->getId(),
                        'title' => $Field->getTitle(),
                        'fieldType' => $Field->getType()
                    ]
                );

                throw new QUI\ERP\Products\Product\Exception(
                    [
                        'quiqqer/products',
                        'exception.field.invalid',
                        [
                            'fieldId' => $Field->getId(),
                            'fieldTitle' => $Field->getTitle(),
                            'fieldType' => $Field->getType()
                        ]
                    ],
                    1003
                );
            }

            if ($Field->isEmpty()) {
                throw new QUI\ERP\Products\Product\Exception(
                    [
                        'quiqqer/products',
                        'exception.field.required.but.empty',
                        [
                            'fieldId' => $Field->getId(),
                            'fieldTitle' => $Field->getTitle(),
                            'fieldType' => $Field->getType()
                        ]
                    ],
                    1004
                );
            }

            $fieldData[] = $Field->toProductArray();
        }

        return $fieldData;
    }

    /**
     * Return the field data of all fields
     * if the product is active, the fields would be validated, too
     *
     * @return array
     */
    protected function getFieldData()
    {
        // Update price fields by factors
        $this->updateProductPricesByFactors();

        $fields = $this->getAllProductFields();
        $fieldData = [];

        /* @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            $this->setUnassignedStatusToField($Field);

            $field = array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );

            $fieldData[] = $field;
        }

        return $fieldData;
    }

    /**
     * Set the unassigned status to a field
     * checks the unassigned status for a field
     * looks into each category
     *
     * @param FieldInterface $Field
     */
    protected function setUnassignedStatusToField($Field)
    {
        if (
            $Field->isSystem()
            || $Field->isStandard()
            || $Field->isOwnField()
        ) {
            $Field->setUnassignedStatus(false);

            return;
        }

        $categories = $this->getCategories();

        /* @var $Category Category */
        foreach ($categories as $Category) {
            $CategoryField = $Category->getField($Field->getId());

            if ($CategoryField) {
                $Field->setUnassignedStatus(false);

                return;
            }
        }

        $Field->setUnassignedStatus(true);
    }

    /**
     * Return all product fields
     * looks at catgeories for missing fields
     *
     * @return QUI\ERP\Products\Field\Field[]
     */
    protected function getAllProductFields()
    {
        $fields = $this->fields;
        $categories = $this->getCategories();

        $categoryFields = [];

        /* @var $Field FieldInterface */
        /* @var $Category Category */

        // get category field data
        foreach ($categories as $Category) {
            $categoryData[] = $Category->getId();
            $catFields = $Category->getFields();

            foreach ($catFields as $Field) {
                $categoryFields[$Field->getId()] = true;
            }
        }

        // helper function
        $isFieldIdInArray = function ($fieldId, $array) {
            /* @var $Field FieldInterface */
            foreach ($array as $Field) {
                if ($Field->getId() == $fieldId) {
                    return true;
                }
            }

            return false;
        };

        // look if the product miss some category fields
        foreach ($categoryFields as $fieldId => $val) {
            if (isset($Category) && $isFieldIdInArray($fieldId, $fields) === false) {
                $CategoryField = $Category->getField($fieldId);

                if ($CategoryField) {
                    $fields[] = $CategoryField;
                }
            }
        }

        return $fields;
    }

    /**
     * Updates the cache table with current product data
     *
     * @return void
     * @throws QUI\Exception
     */
    public function updateCache()
    {
        if (!Products::$updateProductSearchCache) {
            return;
        }

        $languages = QUI::availableLanguages();

        foreach ($languages as $lang) {
            $this->writeCacheEntry($lang);
        }
    }

    /**
     * Write cache entry for product for specific language
     *
     * @param string $lang
     * @throws QUI\Exception
     */
    protected function writeCacheEntry($lang)
    {
//        $Locale = new QUI\Locale();
        $Locale = Products::getLocale();
        $current = $Locale->getCurrent();

        $Locale->setCurrent($lang);

        // wir nutzen system user als netto user
        $SystemUser = QUI::getUsers()->getSystemUser();

        try {
            $minPrice = $this->getMinimumPrice($SystemUser)->value();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            $minPrice = false;
        }

        try {
            $maxPrice = $this->getMaximumPrice($SystemUser)->value();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            $maxPrice = false;
        }

        try {
            $currentPrice = $this->getCurrentPrice($SystemUser)->value();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            $currentPrice = false;
        }

        // Dates
        $cDate = $this->getAttribute('c_date');

        if (empty($cDate) || $cDate === '0000-00-00 00:00:00') {
            $cDate = date('Y-m-d H:i:s');
        }

        $eDate = $this->getAttribute('e_date');

        if (empty($eDate) || $eDate === '0000-00-00 00:00:00') {
            $eDate = date('Y-m-d H:i:s');
        }

        // type
        $type = QUI\ERP\Products\Product\Types\Product::class;
        $productType = $this->getAttribute('type');
        $ProductTypes = QUI\ERP\Products\Utils\ProductTypes::getInstance();

        if ($ProductTypes->exists($productType)) {
            $type = $productType;
        }

        $title = $this->getFieldValueByLocale(
            Fields::FIELD_TITLE,
            $Locale
        );

        if (empty($title)) {
            $title = '';
        }

        $data = [
            'type' => $type,
            'productNo' => $this->getFieldValueByLocale(
                Fields::FIELD_PRODUCT_NO,
                $Locale
            ),
            'title' => $title,
            'description' => $this->getFieldValueByLocale(
                Fields::FIELD_SHORT_DESC,
                $Locale
            ),
            'active' => $this->isActive() ? 1 : 0,
            'minPrice' => $minPrice ?: 0,
            'maxPrice' => $maxPrice ?: 0,
            'currentPrice' => $currentPrice ?: 0,
            'c_date' => $cDate,
            'e_date' => $eDate
        ];

        if ($this instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $data['parentId'] = $this->getParent()->getId();
        }

        // permissions
        $permissions = $this->getPermissions();
        $viewPermissions = null;

        if (isset($permissions['permission.viewable']) && !empty($permissions['permission.viewable'])) {
            $viewPermissions = ',' . $permissions['permission.viewable'] . ',';
        }

        $data['viewUsersGroups'] = $viewPermissions;

        // get all categories
        $categories = $this->getCategories();

        if (!empty($categories)) {
            $catIds = [];

            /** @var Category $Category */
            foreach ($categories as $Category) {
                $catIds[] = $Category->getId();
            }

            $data['category'] = ',' . implode(',', $catIds) . ',';
        } else {
            $data['category'] = null;
        }

        $fields = $this->getFields();

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            if (!$Field->isSearchable()) {
                continue;
            }

            $fieldColumnName = SearchHandler::getSearchFieldColumnName($Field);
            $data[$fieldColumnName] = $Field->getSearchCacheValue($Locale);

            if ($Field->getId() == Fields::FIELD_PRIORITY && empty($data[$fieldColumnName])) {
                // in 10 Jahren darf mor das fixen xD
                // null und 0 wird als letztes angezeigt
                $data[$fieldColumnName] = 999999;
            }
        }

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = json_encode($v);
            }
        }

        // test if cache entry exists first
        $result = QUI::getDataBase()->fetch([
            'from' => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'where' => [
                'id' => $this->getId(),
                'lang' => $lang
            ]
        ]);

        // set current lang back
        $Locale->setCurrent($current);

        if (empty($result)) {
            $data['id'] = $this->id;
            $data['lang'] = $lang;

            QUI::getDataBase()->insert(
                QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                $data
            );

            return;
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            $data,
            [
                'id' => $this->getId(),
                'lang' => $lang
            ]
        );
    }

    /**
     * delete the complete product
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI\Permissions\Permission::checkPermission('product.delete');

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.delete', [
                'id' => $this->getId(),
                'title' => $this->getTitle(),
            ])
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductDeleteBegin', [$this]);

        // delete the media folder
        try {
            $MediaFolder = $this->getMediaFolder();
            $delete = true;

            if (
                $this instanceof QUI\ERP\Products\Product\Types\VariantChild
                && $MediaFolder->getId() === $this->getParent()->getMediaFolder()->getId()
            ) {
                $delete = false;
            }

            if ($delete) {
                $MediaFolder->delete();
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }


        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            ['id' => $this->getId()]
        );

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            ['id' => $this->getId()]
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductDelete', [$this]);
    }

    /**
     * Field methods
     */

    /**
     * Return the product fields
     *
     * @return FieldInterface[]
     */
    public function getFields()
    {
        $fields = [];

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->fields as $Field) {
            if (
                !$Field->isUnassigned()
                // quiqqer/products#291
                // || $Field->getType() === Fields::TYPE_ATTRIBUTE_GROUPS
                // || $Field->getType() === Fields::TYPE_ATTRIBUTE_LIST
            ) {
                $fields[$Field->getId()] = $Field;
            }
        }

        return QUI\ERP\Products\Utils\Fields::sortFields($fields);
    }

    /**
     * Return all fields from the specific type
     *
     * @param string|array $type - field type (eq: ProductAttributeList, Price ...) or list of field types
     * @return FieldInterface[]
     */
    public function getFieldsByType($type)
    {
        if (!is_array($type)) {
            $type = [$type];
        }

        $type = array_flip($type);

        $result = [];
        $fields = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if (isset($type[$Field->getType()])) {
                $result[] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return the field
     *
     * @param integer|string $fieldId - Field ID or FIELD constant name -> FIELD_PRICE, FIELD_PRODUCT_NO ...
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getField($fieldId)
    {
        if (is_string($fieldId) && defined('QUI\ERP\Products\Handler\Fields::' . $fieldId)) {
            $fieldId = \constant('QUI\ERP\Products\Handler\Fields::' . $fieldId);
        }

        if (isset($this->fields[$fieldId])) {
            return $this->fields[$fieldId];
        }

        throw new QUI\ERP\Products\Product\Exception(
            [
                'quiqqer/products',
                'exception.field.id_in_product_not_found',
                [
                    'fieldId' => $fieldId,
                    'productId' => $this->getId()
                ]
            ],
            1002
        );
    }

    /**
     * Has the product the field?
     *
     * @param Integer $fieldId
     * @return bool
     */
    public function hasField($fieldId)
    {
        return isset($this->fields[$fieldId]);
    }

    /**
     * Return the field value
     *
     * @param integer|string $fieldId - Field ID or FIELD constant name -> FIELD_PRICE, FIELD_PRODUCT_NO ...
     * @return mixed
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getFieldValue($fieldId)
    {
        return $this->getField($fieldId)->getValue();
    }

    /**
     * Return the field value
     *
     * @param integer $fieldId
     * @param QUI\Locale $Locale (optional)
     * @return mixed
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getFieldValueByLocale($fieldId, $Locale = null)
    {
        return $this->getField($fieldId)->getValueByLocale($Locale);
    }

    /**
     * @param $fieldId
     * @return array
     *
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getFieldSource($fieldId)
    {
        $sources = [];
        $Field = $this->getField($fieldId);
        $categories = $this->getCategories();

        if ($Field->isPublic()) {
            $sources[] = QUI::getLocale()->get('quiqqer/products', 'publicField');
        }

        if ($Field->isSystem()) {
            $sources[] = QUI::getLocale()->get('quiqqer/products', 'systemField');
        }

        if ($Field->isStandard()) {
            $sources[] = QUI::getLocale()->get('quiqqer/products', 'standardField');
        }

        $found = Categories::getCategoryIds([
            'where' => [
                'fields' => [
                    'type' => '%LIKE%',
                    'value' => '"id":' . $Field->getId() . ','
                ]
            ]
        ]);

        $isIdInCategories = function ($cid) use ($categories) {
            /* @var $Category Category */
            foreach ($categories as $Category) {
                if ($Category->getId() == $cid) {
                    return true;
                }
            }

            return false;
        };

        foreach ($found as $cid) {
            if ($isIdInCategories($cid)) {
                $sources[] = Categories::getCategory($cid)->getTitle();
            }
        }

        return $sources;
    }

    /**
     * Category methods
     */

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Return the main category
     *
     * @return Category|null
     */
    public function getCategory()
    {
        // fallback, but never happen
        if (\is_null($this->Category)) {
            $categories = $this->getCategories();

            if (count($categories)) {
                \reset($categories);
                $this->Category = \current($categories);
            }
        }

        // fallback, but never happen
        if (\is_null($this->Category)) {
            try {
                $this->Category = Categories::getMainCategory();
            } catch (QUI\Exception $Exception) {
            }
        }

        return $this->Category;
    }

    /**
     * Remove the product from all categories
     */
    public function clearCategories()
    {
        $this->categories = [];
    }

    /**
     * Remove the product from the category
     *
     * @param integer $categoryId
     */
    public function removeCategory($categoryId)
    {
        if (isset($this->categories[$categoryId])) {
            unset($this->categories[$categoryId]);
        }
    }

    /**
     * Image / File methods
     */

    /**
     * Return the product media folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception|QUI\ERP\Products\Product\Exception
     */
    public function getMediaFolder()
    {
        $folderUrl = $this->getFieldValue(Fields::FIELD_FOLDER);
        $Folder = MediaUtils::getMediaItemByUrl($folderUrl);

        if (MediaUtils::isFolder($Folder)) {
            /* @var $Folder QUI\Projects\Media\Folder */
            return $Folder;
        }

        throw new QUI\ERP\Products\Product\Exception([
            'quiqqer/products',
            'exception.products.media.folder.missing'
        ]);
    }

    /**
     * Return the main product image
     *
     * @return QUI\Projects\Media\Image
     * @throws QUI\Exception
     */
    public function getImage()
    {
        try {
            $value = $this->getFieldValue(Fields::FIELD_IMAGE);
            $Image = MediaUtils::getImageByUrl($value);

            return $Image;
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Folder = $this->getMediaFolder();

            if ($Folder) {
                $images = $Folder->getImages([
                    'limit' => 1,
                    'order' => 'priority ASC'
                ]);

                if (isset($images[0])) {
                    return $images[0];
                }
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Project = QUI::getRewrite()->getProject();
            $Media = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder) {
                return $Placeholder;
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Project = QUI::getProjectManager()->getStandard();
            $Media = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder) {
                return $Placeholder;
            }
        } catch (QUI\Exception $Exception) {
        }

        throw new QUI\ERP\Products\Product\Exception([
            'quiqqer/products',
            'exception.product.no.image',
            [
                'productId' => $this->getId()
            ]
        ]);
    }

    /**
     * Has the product an image?
     *
     * @return bool
     */
    public function hasImage()
    {
        try {
            $this->getImage();
        } catch (QUI\Exception $Exception) {
            return false;
        }

        return true;
    }

    /**
     * Return all images for the product
     *
     * @param array $params - optional, select params
     * @return array
     */
    public function getImages($params = [])
    {
        try {
            return $this->getMediaFolder()->getImages($params);
        } catch (QUI\Exception $Exception) {
            return [];
        }
    }

    /**
     * Return all files for the product
     *
     * @param array $params - optional, select params
     * @return array
     */
    public function getFiles($params = [])
    {
        try {
            return $this->getMediaFolder()->getFiles($params);
        } catch (QUI\Exception $Exception) {
            return [];
        }
    }

    /**
     * Deactivate the product
     *
     * @param QUI\Interfaces\Users\User $EditUser (optional) - The user that executes the operation
     * @return void
     *
     * @throws QUI\Exception
     */
    public function deactivate($EditUser = null)
    {
        if (empty($EditUser)) {
            $EditUser = QUI::getUserBySession();
        }

        QUI\Permissions\Permission::checkPermission('product.activate', $EditUser);

        $this->active = false;

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.deactivate', [
                'id' => $this->getId()
            ])
        );

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            ['active' => 0],
            ['id' => $this->getId()]
        );

        $this->updateCache();
        $this->buildCache();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductDeactivate', [$this]);
    }

    /**
     * Activate the product
     *
     * @param QUI\Interfaces\Users\User $EditUser (optional) - The user that executes the operation
     * @return void
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     */
    public function activate($EditUser = null)
    {
        if (empty($EditUser)) {
            $EditUser = QUI::getUserBySession();
        }

        QUI\Permissions\Permission::checkPermission('product.activate', $EditUser);

        // exist a main category?
        $Category = $this->getCategory();

        if (!$Category) {
            throw new QUI\ERP\Products\Product\Exception([
                'quiqqer/products',
                'exception.product.activasion.no.category',
                [
                    'id' => $this->getId(),
                    'title' => $this->getTitle()
                ]
            ]);
        }

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.activate', [
                'id' => $this->getId()
            ])
        );

        // duplicate article no. check
        $articleNo = $this->getFieldValue(Fields::FIELD_PRODUCT_NO);

        if (!empty($articleNo)) {
            $this->checkDuplicateArticleNo($articleNo);
        }

        // all fields correct?
        $this->validateFields();

        $this->active = true;

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            ['active' => 1],
            ['id' => $this->getId()]
        );

        $this->updateCache();
        $this->buildCache();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductActivate', [$this]);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Own Product Permissions
     */

    /**
     * Has the user the product permission?
     *
     * @param string $permission - Permission name
     * @param QUI\Interfaces\Users\User $User
     * @return bool
     */
    public function hasPermission($permission, $User = null)
    {
        if (!Products::usePermissions()) {
            return true;
        }

        if (!$User) {
            $User = QUI::getUserBySession();
        }


        $permissions = '';

        if (isset($this->permissions[$permission])) {
            $permissions = $this->permissions[$permission];
        }

        if (empty($permissions)) {
            return true;
        }

        return QUI\Utils\UserGroups::isUserInUserGroupString($User, $permissions);
    }

    /**
     * Check the user product permission
     *
     * @param $permission
     * @param null $User
     * @throws QUI\Permissions\Exception
     */
    public function checkPermission($permission, $User = null)
    {
        if (!$User) {
            $User = QUI::getUserBySession();
        }

        if (!$this->hasPermission($permission, $User)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403,
                [
                    'userid' => $User->getId(),
                    'username' => $User->getName()
                ]
            );
        }
    }

    /**
     * @return array|mixed
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Clear the complete own product permissions
     *
     * @param QUI\Interfaces\Users\User $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function clearPermissions($User = null)
    {
        QUI\Permissions\Permission::checkPermission('product.setPermissions', $User);

        $this->permissions = [];
    }

    /**
     * Clear a product own permission
     *
     * @param string $permission - name of the product permission
     * @param null $User
     * @throws QUI\Permissions\Exception
     */
    public function clearPermission($permission, $User = null)
    {
        QUI\Permissions\Permission::checkPermission('product.setPermissions', $User);

        if (isset($this->permissions[$permission])) {
            $this->permissions[$permission] = [];
        }
    }

    //region currency

    /**
     * @param QUI\ERP\Currency\Currency $Currency
     */
    public function setCurrency(QUI\ERP\Currency\Currency $Currency)
    {
        $this->Currency = $Currency;
    }

    /**
     * @return QUI\ERP\Currency\Currency|null
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    //endregion

    // region Price factors

    /**
     * Determines if all price fields should be updated if they have a price factor assigned
     * REGARDLESS of the "update on save" flag for each price field.
     *
     * @param bool $value
     * @return void
     */
    public function setForcePriceFieldFactorUse(bool $value): void
    {
        $this->forcePriceFactorUse = $value;
    }

    /**
     * Update all price fields by a factor (if set in global settings)
     *
     * @return void
     */
    protected function updateProductPricesByFactors(): void
    {
        // Check if main category of product has own price factor settings
        $MainCategory = $this->getCategory();

        $priceFactors = false;

        if ($MainCategory instanceof Category) {
            $priceFactors = $MainCategory->getCustomDataEntry('priceFieldFactors');
        }

        // Check if any other category of this product has own price factor settings
        if (empty($priceFactors)) {
            $categories = $this->getCategories();

            // sort by id ASC
            \usort($categories, function ($CatA, $CatB) {
                /**
                 * @var Category $CatA
                 * @var Category $CatB
                 */
                return $CatA->getId() - $CatB->getId();
            });

            foreach ($categories as $Category) {
                if ($Category instanceof Category) {
                    $priceFactors = $Category->getCustomDataEntry('priceFieldFactors');

                    if (!empty($priceFactors)) {
                        break;
                    }
                }
            }
        }

        // If no category has price factor settings -> use global settings
        if (empty($priceFactors)) {
            $priceFactors = Fields::getPriceFactorSettings();
        }

        foreach ($priceFactors as $priceFieldId => $settings) {
            if (!$this->hasField($priceFieldId) || !$this->hasField($settings['sourceFieldId'])) {
                continue;
            }

            if (empty($settings['updateOnSave']) && !$this->forcePriceFactorUse) {
                continue;
            }

            try {
                $PriceField = $this->getField($priceFieldId);
                $SourceField = $this->getField($settings['sourceFieldId']);
                $multiplier = (float)$settings['multiplier'];

                if (empty($SourceField->getValue())) {
                    continue;
                }

                $price = $SourceField->getValue() * $multiplier;

                // Rounding
                if (!empty($settings['rounding']['type'])) {
                    $vatPercent = 0;

                    if (!empty($settings['rounding']['vat'])) {
                        $vatPercent = (float)$settings['rounding']['vat'];
                    }

                    $vat = (100 + $vatPercent) / 100;
                    $targetPrice = $price * $vat;
                    $targetPriceParts = explode('.', $targetPrice);

                    $targetPriceInt = (int)$targetPriceParts[0];

                    if (!empty($targetPriceParts[1])) {
                        $targetPriceDecimals = $targetPriceParts[1];
                    } else {
                        $targetPriceDecimals = 0;
                    }

                    $buildPriceByConcat = true;

                    switch ($settings['rounding']['type']) {
                        case 'up':
                            $targetPriceInt = ceil($targetPriceInt / 10) * 10;
                            break;

                        case 'up_9':
                            $targetPriceInt = (ceil($targetPriceInt / 10) * 10) - 1;
                            break;

                        case 'down':
                            $targetPriceInt = floor($targetPriceInt / 10) * 10;
                            break;

                        case 'down_9':
                            $targetPriceInt = (floor($targetPriceInt / 10) * 10) - 1;
                            break;

                        case 'commercial':
                            $targetPriceInt = round($targetPriceInt / 10) * 10;
                            break;

                        case 'commercial_9':
                            $targetPriceInt = (round($targetPriceInt / 10) * 10) - 1;
                            break;

                        case 'commercial_decimals':
                            $targetPrice = \round($targetPrice);
                            $buildPriceByConcat = false;
                            break;

                        case 'commercial_decimals_single':
                            $targetPrice = \round($targetPrice, 1);
                            $buildPriceByConcat = false;
                            break;
                    }

                    if ($buildPriceByConcat) {
                        if (!empty($settings['rounding']['custom'])) {
                            $targetPrice = $targetPriceInt . '.' . $settings['rounding']['custom'];
                        } else {
                            $targetPrice = $targetPriceInt . '.' . $targetPriceDecimals;
                        }
                    }

                    $price = (float)$targetPrice / $vat;
                }

                $PriceField->setValue($price);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }

    // endregion

    // region Validation

    /**
     * Check if there is another active product with an identical article no.
     *
     * @param string $articleNo - The article no. to check against
     * @return void
     *
     * @throws QUI\Exception - Thrown if a duplicate article no. exists
     */
    protected function checkDuplicateArticleNo(string $articleNo): void
    {
        $subQuery = "SELECT `id` FROM " . QUI\ERP\Products\Utils\Tables::getProductTableName();
        $subQuery .= " WHERE `active` = 1 AND `parent` IS NULL";

        $sql = "SELECT `id` FROM " . QUI\ERP\Products\Utils\Tables::getProductCacheTableName();
        $sql .= " WHERE `id` != " . $this->getId() . " AND `active` = 1";
        $sql .= " AND (`parentId` IS NULL or `parentId` IN(" . $subQuery . "))";
        $sql .= " AND `productNo` = '" . $articleNo . "'";

        $result = QUI::getDataBase()->fetchSQL($sql);
        $duplicateArticleNoProductIds = array_unique(\array_column($result, 'id'));

        foreach ($duplicateArticleNoProductIds as $productId) {
            if (Products::existsProduct($productId)) {
                throw new QUI\ERP\Products\Product\Exception(
                    [
                        'quiqqer/products',
                        'exception.duplicate_article_no',
                        [
                            'articleNo' => $articleNo,
                            'otherProductId' => $productId
                        ]
                    ],
                    400,
                    [
                        'updateProduct' => $this->getId()
                    ]
                );
            }
        }
    }

    // endregion
}
