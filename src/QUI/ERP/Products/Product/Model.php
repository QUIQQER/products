<?php

/**
 * This file contains QUI\ERP\Products\Product\Model
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Products;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\Security\Orthos;
use QUI\ERP\Products\Handler\Search as SearchHandler;

/**
 * Class Controller
 * Product Model
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
    protected $fields = array();

    /**
     * @var array
     */
    protected $categories = array();

    /**
     * Permissions list
     * @var array
     */
    protected $permissions = array();

    /**
     * @var null
     */
    protected $Category = null;

    /**
     * Active / Deactive status
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Model constructor
     *
     * @param integer $pid - Product-ID
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function __construct($pid)
    {
        $this->id = (int)$pid;

        $result = QUI::getDataBase()->fetch(array(
            'from'  => QUI\ERP\Products\Utils\Tables::getProductTableName(),
            'where' => array(
                'id' => $this->getId()
            )
        ));

        if (!isset($result[0])) {
            // if not exists, so we cleanup the cache table table, too
            QUI::getDataBase()->delete(
                QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                array('id' => $this->getId())
            );

            throw new QUI\ERP\Products\Product\Exception(
                array(
                    'quiqqer/products',
                    'exception.product.not.found',
                    array('productId' => $this->getId())
                ),
                404,
                array('id' => $this->getId())
            );
        }

        $this->active = (int)$result[0]['active'] ? true : false;

        if (isset($result[0]['permissions'])) {
            $this->permissions = json_decode($result[0]['permissions'], true);
        }

        // view permissions prüfung wird im Frontend view gemacht (ViewFrontend)


        unset($result[0]['id']);
        unset($result[0]['active']);

        $this->setAttributes($result[0]);

        // categories
        $categories = explode(',', trim($result[0]['categories'], ','));

        if (is_array($categories)) {
            foreach ($categories as $categoryId) {
                try {
                    $Category = QUI\ERP\Products\Handler\Categories::getCategory($categoryId);

                    $this->categories[$Category->getId()] = $Category;
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
        $fields = json_decode($result[0]['fieldData'], true);

        if (!is_array($fields)) {
            $fields = array();
        }

        foreach ($fields as $field) {
            if (!isset($field['id']) && !isset($field['value'])) {
                continue;
            }

            try {
                $Field = Fields::getField($field['id']);
                $Field->setValue($field['value']);

                if (isset($field['unassigned'])) {
                    $Field->setUnassignedStatus($field['unassigned']);
                }

                if (isset($field['ownField'])) {
                    $Field->setOwnFieldStatus($field['ownField']);
                }

                if (isset($field['isPublic'])) {
                    $Field->setPublicStatus((bool)$field['isPublic']);
                }

                $this->fields[$Field->getId()] = $Field;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_DEBUG);
            }
        }

        // all standard and all system fields must be in the product
        $systemfields = Fields::getFields(array(
            'where_or' => array(
                'systemField'   => 1,
                'standardField' => 1
            )
        ));

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($systemfields as $Field) {
            if (!isset($this->fields[$Field->getId()])) {
                $this->fields[$Field->getId()] = $Field;
                continue;
            }
        }

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }
    }

    /**
     * Return the duly view
     *
     * @return ViewFrontend|ViewBackend
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
     */
    public function createUniqueProduct($User = null)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        $Locale    = $User->getLocale();
        $fieldList = $this->getFields();

        $attributes                = $this->getAttributes();
        $attributes['title']       = $this->getTitle($Locale);
        $attributes['description'] = $this->getDescription($Locale);
        $attributes['uid']         = $User->getId();

        $fields = array();

        foreach ($fieldList as $Field) {
            /* @var $Field QUI\ERP\Products\Field\CustomField */
            if ($Field->isCustomField()) {
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

        return new UniqueProduct($this->getId(), $attributes);
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
                throw new QUI\ERP\Products\Product\Exception(array(
                    'quiqqer/products',
                    'exception.product.field.is.no.media.folder'
                ));
            }

            // exist a media folder in the field?
            try {
                $folderUrl = $this->getFieldValue($fieldId);
                $Folder    = MediaUtils::getMediaItemByUrl($folderUrl);

                if (MediaUtils::isFolder($Folder)) {
                    /* @var $Folder QUI\Projects\Media\Folder */
                    return $Folder;
                }
            } catch (QUI\Exception $Exception) {
            }


            $MainFolder = $this->createMediaFolder();

            try {
                $Folder = $MainFolder->createFolder($fieldId);
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
            $Folder = $Parent->createFolder($this->getId());
        } catch (QUI\Exception $Exception) {
            if ($Exception->getCode() != 701) {
                throw $Exception;
            }

            $Folder = $Parent->getChildByName($this->getId());
        }

        $Field = $this->getField(Fields::FIELD_FOLDER);
        $Field->setValue($Folder->getUrl());

        $this->update();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCreateMediaFolder', array($this));

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
     *
     * @return string
     */
    public function getUrl()
    {
        $Category = $this->getCategory();
        $Site     = $Category->getSite();

        if ($Site->getAttribute('type') !== 'quiqqer/products:types/category'
            && $Site->getAttribute('type') !== 'quiqqer/products:types/search'
        ) {
            QUI\System\Log::addWarning(
                QUI::getLocale()->get('quiqqer/products', 'exception.product.url.missing', array(
                    'productId' => $this->getId(),
                    'title'     => $this->getTitle()
                ))
            );
        }

        $url = $Site->getUrlRewritten(array(
            0              => $this->getUrlName(),
            'paramAsSites' => true
        ));

        return $url;
    }

    /**
     * Return name for rewrite url
     *
     * @return string
     */
    public function getUrlName()
    {
        $parts   = array();
        $parts[] = Orthos::urlEncodeString($this->getTitle());
        $parts[] = $this->getId();

        return urlencode(implode(QUI\Rewrite::URL_PARAM_SEPERATOR, $parts));
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
                array('id' => $this->getId())
            ),
            array(
                'id' => $this->getId()
            )
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
            $data  = $Field->getValue();

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
                if (!empty($data[$lang])) {
                    return $data[$lang];
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
     * Beachtet alle Preisfelder und sucht das zu diesem Zeitpunkt richtig Preisfeld
     *
     * @param null|QUI\Interfaces\Users\User $User - optional, default = Nobody
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice($User = null)
    {
        return QUI\ERP\Products\Utils\Products::getPriceFieldForProduct($this, $User);
    }

    /**
     * Alias for getPrice
     * So, the Product has the same construction as the UniqueProduct
     *
     * @param null|QUI\Interfaces\Users\User $User
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getNettoPrice($User = null)
    {
        return $this->getPrice($User);
    }

    /**
     * Return the minimum price
     *
     * @param null $User
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getMinimumPrice($User = null)
    {
        $cacheName = 'quiqqer/products/' . $this->getId() . '/prices/min';

        try {
            $data     = QUI\Cache\Manager::get($cacheName);
            $Currency = QUI\ERP\Currency\Handler::getCurrency($data['currency']);

            return new QUI\ERP\Products\Utils\Price($data['price'], $Currency);
        } catch (QUI\Exception $Exception) {
        }

        // search all custom fields, and set the minimum
        $Clone  = new Product($this->getId());
        $Calc   = QUI\ERP\Products\Utils\Calc::getInstance($User); // @todo netto user nutzen
        $fields = $Clone->getFields();

        // alle felder müssen erst einmal gesetzt werden
        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if (!$Field->isCustomField()) {
                continue;
            }

            $options = $Field->getOptions();

            if (isset($options['entries']) && count($options['entries'])) {
                $Clone->getField($Field->getId())->setValue(0);
            }
        }

        $Price        = $Clone->createUniqueProduct()->calc($Calc)->getPrice();
        $currentPrice = $Price->getNetto();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if (!$Field->isCustomField()) {
                continue;
            }

            $options = $Field->getOptions();

            if (!isset($options['entries'])) {
                continue;
            }

            foreach ($options['entries'] as $index => $data) {
                $Clone->getField($Field->getId())->setValue($index);

                $price = $Clone->createUniqueProduct()->calc($Calc)->getPrice()->getNetto();

                if ($currentPrice > $price) {
                    $currentPrice = $price;
                }
            }
        }

        $Result = new QUI\ERP\Products\Utils\Price($currentPrice, $Price->getCurrency());

        QUI\Cache\Manager::set($cacheName, $Result->toArray());

        return $Result;
    }

    /**
     * Return the maximum price
     *
     * @param null $User
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getMaximumPrice($User = null)
    {
        $cacheName = 'quiqqer/products/' . $this->getId() . '/prices/max';

        try {
            $data     = QUI\Cache\Manager::get($cacheName);
            $Currency = QUI\ERP\Currency\Handler::getCurrency($data['currency']);

            return new QUI\ERP\Products\Utils\Price($data['price'], $Currency);
        } catch (QUI\Exception $Exception) {
        }

        $Clone  = new Product($this->getId());
        $Calc   = QUI\ERP\Products\Utils\Calc::getInstance($User);
        $fields = $Clone->getFields();

        $Price        = $Clone->createUniqueProduct()->calc($Calc)->getPrice();
        $currentPrice = $Price->getNetto();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if (!$Field->isCustomField()) {
                continue;
            }

            $options = $Field->getOptions();

            if (!isset($options['entries'])) {
                continue;
            }

            foreach ($options['entries'] as $index => $data) {
                $Clone->getField($Field->getId())->setValue($index);

                $price = $Clone->createUniqueProduct()->calc($Calc)->getPrice()->getNetto();

                if ($currentPrice < $price) {
                    $currentPrice = $price;
                }
            }
        }

        $Result = new QUI\ERP\Products\Utils\Price($currentPrice, $Price->getCurrency());

        QUI\Cache\Manager::set($cacheName, $Result->toArray());

        return $Result;
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['id']          = $this->getId();
        $attributes['active']      = $this->isActive();
        $attributes['title']       = $this->getTitle();
        $attributes['description'] = $this->getDescription();
        $attributes['permissions'] = $this->getPermissions();
        $attributes['image']       = false;

        try {
            $attributes['image'] = $this->getImage()->getUrl(true);
        } catch (QUI\Exception $Exception) {
        }


        /* @var $Price QUI\ERP\Products\Utils\Price */
        $Price = $this->getPrice();

        $attributes['price_netto']    = $Price->getNetto();
        $attributes['price_currency'] = $Price->getCurrency()->getCode();

        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        }

        // fields
        $fields    = array();
        $fieldList = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fieldList as $Field) {
            $fields[] = array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );
        }

        if (!empty($fields)) {
            $attributes['fields'] = $fields;
        }

        // categories
        $categories = array();
        $catList    = $this->getCategories();

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
     * @throws QUI\Exception
     */
    public function update()
    {
        $this->save();
    }

    /**
     * save / update the product data
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function save()
    {
        $this->productSave($this->getFieldData());
    }

    /**
     * Internal saving method
     *
     * @param array $fieldData - field data
     *
     * @throws QUI\Permissions\Exception
     */
    protected function productSave($fieldData)
    {
        QUI\Permissions\Permission::checkPermission('product.edit');

        $categoryIds = array();
        $categories  = $this->getCategories();

        /* @var $Field FieldInterface */
        /* @var $Category Category */

        // get category field data
        foreach ($categories as $Category) {
            $categoryIds[] = $Category->getId();
        }


        // set main category
        $mainCategory = '';
        $Category     = $this->getCategory();

        if ($Category) {
            $mainCategory = $Category->getId();
        }

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.save', array(
                'id' => $this->getId()
            )),
            '',
            array(
                'categories'  => ',' . implode(',', $categoryIds) . ',',
                'category'    => $mainCategory,
                'fieldData'   => json_encode($fieldData),
                'permissions' => json_encode($this->permissions)
            )
        );

        // update
        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array(
                'categories'  => ',' . implode(',', $categoryIds) . ',',
                'category'    => $mainCategory,
                'fieldData'   => json_encode($fieldData),
                'permissions' => json_encode($this->permissions),
                'e_user'      => QUI::getUserBySession()->getId(),
                'e_date'      => date('Y-m-d H:i:s')
            ),
            array('id' => $this->getId())
        );

        $this->updateCache();

        QUI\Cache\Manager::clear('quiqqer/products/' . $this->getId());

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductSave', array($this));
    }

    /**
     * save / update the product data
     * and check the product fields if the product is active
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function userSave()
    {
        if ($this->isActive()) {
            $fieldData = $this->validateFields();
        } else {
            $fieldData = $this->getFieldData();
        }

        $this->productSave($fieldData);

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductUserSave', array($this));
    }

    /**
     * Validate the fields and return the field data
     *
     * @return array
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function validateFields()
    {
        $fieldData = array();
        $fields    = $this->getAllProductFields();

        // generate the product field data
        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            $value = $Field->getValue();

            $this->setUnassignedStatusToField($Field);

            if (!$Field->isRequired() || $Field->isCustomField()) {
                $Field->validate($value);

                $fieldData[] = $Field->toProductArray();
                continue;
            }

            try {
                $Field->validate($value);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning(
                    $Exception->getMessage(),
                    array(
                        'id'        => $Field->getId(),
                        'title'     => $Field->getTitle(),
                        'fieldType' => $Field->getType()
                    )
                );

                throw new QUI\ERP\Products\Product\Exception(
                    array(
                        'quiqqer/products',
                        'exception.field.invalid',
                        array(
                            'fieldId'    => $Field->getId(),
                            'fieldTitle' => $Field->getTitle(),
                            'fieldType'  => $Field->getType()
                        )
                    ),
                    1003
                );
            }

            if ($Field->isEmpty()) {
                throw new QUI\ERP\Products\Product\Exception(
                    array(
                        'quiqqer/products',
                        'exception.field.required.but.empty',
                        array(
                            'fieldId'    => $Field->getId(),
                            'fieldTitle' => $Field->getTitle(),
                            'fieldType'  => $Field->getType()
                        )
                    ),
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
        $fields    = $this->getAllProductFields();
        $fieldData = array();

        /* @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            $this->setUnassignedStatusToField($Field);

            $fieldData[] = $Field->toProductArray();
        }

        return $fieldData;
    }

    /**
     * Set the unasigned status to a field
     * checks the unassigned status for a field
     * looks into each category
     *
     * @param FieldInterface $Field
     */
    protected function setUnassignedStatusToField($Field)
    {
        if ($Field->isSystem()
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
     * @return array
     */
    protected function getAllProductFields()
    {
        $fields     = $this->fields;
        $categories = $this->getCategories();

        $categoryFields = array();

        /* @var $Field FieldInterface */
        /* @var $Category Category */

        // get category field data
        foreach ($categories as $Category) {
            $categoryData[] = $Category->getId();
            $catFields      = $Category->getFields();

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
            if ($isFieldIdInArray($fieldId, $fields) === false) {
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
        $langs = QUI::availableLanguages();

        foreach ($langs as $lang) {
            $this->writeCacheEntry($lang);
        }
    }

    /**
     * Write cache entry for product for specific language
     *
     * @param string $lang
     */
    protected function writeCacheEntry($lang)
    {
        $Locale = new QUI\Locale();
        $Locale->setCurrent($lang);

        $data = array(
            'productNo' => $this->getFieldValueByLocale(
                Fields::FIELD_PRODUCT_NO,
                $Locale
            ),
            'title'     => $this->getFieldValueByLocale(
                Fields::FIELD_TITLE,
                $Locale
            ),
            'active'    => $this->isActive() ? 1 : 0,
            'minPrice'  => $this->getMinimumPrice()->getNetto(),
            'maxPrice'  => $this->getMaximumPrice()->getNetto()
        );

        // permissions
        $permissions     = $this->getPermissions();
        $viewPermissions = null;

        if (isset($permissions['permission.viewable'])
            && !empty($permissions['permission.viewable'])
        ) {
            $viewPermissions = ',' . $permissions['permission.viewable'] . ',';
        }

        $data['viewUsersGroups'] = $viewPermissions;

        // get all categories
        $categories = $this->getCategories();

        if (!empty($categories)) {
            $catIds = array();

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

            $fieldColumnName        = SearchHandler::getSearchFieldColumnName($Field);
            $data[$fieldColumnName] = $Field->getSearchCacheValue($Locale);
        }

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = json_encode($v);
            }
        }

        // test if cache entry exists first
        $result = QUI::getDataBase()->fetch(array(
            'from'  => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'where' => array(
                'id'   => $this->getId(),
                'lang' => $lang
            )
        ));

        if (empty($result)) {
            $data['id']   = $this->id;
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
            array(
                'id'   => $this->getId(),
                'lang' => $lang
            )
        );
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        QUI\Permissions\Permission::checkPermission('product.delete');

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.delete', array(
                'id'    => $this->getId(),
                'title' => $this->getTitle(),
            ))
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductDeleteBegin', array($this));

        // delete the media folder
        try {
            $MediaFolder = $this->getMediaFolder();
            $MediaFolder->delete();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }


        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('id' => $this->getId())
        );

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            array('id' => $this->getId())
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductDelete', array($this));
    }

    /**
     * Field methods
     */

    /**
     * Return the product fields
     *
     * @return array
     */
    public function getFields()
    {
        $fields = array();

        /* @var $Field FieldInterface */
        foreach ($this->fields as $Field) {
            if (!$Field->isUnassigned()) {
                $fields[$Field->getId()] = $Field;
            }
        }

        return QUI\ERP\Products\Utils\Fields::sortFields($fields);
    }

    /**
     * Return all fields from the specific type
     *
     * @param string $type - field type (eq: ProductAttributeList, Price ...)
     * @return array
     */
    public function getFieldsByType($type)
    {
        $result = array();
        $fields = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if ($Field->getType() == $type) {
                $result[] = $Field;
            }
        }

        return QUI\ERP\Products\Utils\Fields::sortFields($result);
    }

    /**
     * Return the field
     *
     * @param integer $fieldId
     * @return QUI\ERP\Products\Field\Field
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getField($fieldId)
    {
        if (isset($this->fields[$fieldId])) {
            return $this->fields[$fieldId];
        }

        throw new QUI\ERP\Products\Product\Exception(
            array(
                'quiqqer/products',
                'exception.field.not.found',
                array(
                    'fieldId'   => $fieldId,
                    'productId' => $this->getId()
                )
            ),
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
     * @param integer $fieldId
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
     * @throws Exception
     */
    public function getFieldSource($fieldId)
    {
        $sources    = array();
        $Field      = $this->getField($fieldId);
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

        $found = Categories::getCategoryIds(array(
            'where' => array(
                'fields' => array(
                    'type'  => '%LIKE%',
                    'value' => '"id":' . $Field->getId() . ','
                )
            )
        ));

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
        if (is_null($this->Category)) {
            $categories = $this->getCategories();

            if (count($categories)) {
                reset($categories);
                $this->Category = current($categories);
            }
        }

        // fallback, but never happen
        if (is_null($this->Category)) {
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
        $this->categories = array();
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
        $Folder    = MediaUtils::getMediaItemByUrl($folderUrl);

        if (MediaUtils::isFolder($Folder)) {
            /* @var $Folder QUI\Projects\Media\Folder */
            return $Folder;
        }

        throw new QUI\ERP\Products\Product\Exception(array(
            'quiqqer/products',
            'exception.products.media.folder.missing'
        ));
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
                $images = $Folder->getImages(array(
                    'limit' => 1,
                    'order' => 'priority ASC'
                ));

                if (isset($images[0])) {
                    return $images[0];
                }
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Project     = QUI::getRewrite()->getProject();
            $Media       = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder) {
                return $Placeholder;
            }
        } catch (QUI\Exception $Exception) {
        }

        throw new QUI\ERP\Products\Product\Exception(array(
            'quiqqer/products',
            'exception.product.no.image',
            array(
                'productId' => $this->getId()
            )
        ));
    }

    /**
     * Return all images for the product
     *
     * @param array $params - optional, select params
     * @return array
     */
    public function getImages($params = array())
    {
        try {
            return $this->getMediaFolder()->getImages($params);
        } catch (QUI\Exception $Exception) {
            return array();
        }
    }

    /**
     * Return all files for the product
     *
     * @param array $params - optional, select params
     * @return array
     */
    public function getFiles($params)
    {
        try {
            return $this->getMediaFolder()->getFiles($params);
        } catch (QUI\Exception $Exception) {
            return array();
        }
    }

    /**
     * Deactivate the product
     */
    public function deactivate()
    {
        QUI\Permissions\Permission::checkPermission('product.activate');

        $this->active = false;

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.activate', array(
                'id' => $this->getId()
            ))
        );

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->updateCache();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductDeactivate', array($this));
    }

    /**
     * Activate the product
     *
     * @throws QUI\ERP\Products\Product\Exception|QUI\Permissions\Exception
     */
    public function activate()
    {
        QUI\Permissions\Permission::checkPermission('product.activate');

        // exist a main category?
        $Category = $this->getCategory();

        if (!$Category) {
            throw new QUI\ERP\Products\Product\Exception(array(
                'quiqqer/products',
                'exception.product.activasion.no.category',
                array(
                    'id'    => $this->getId(),
                    'title' => $this->getTitle()
                )
            ));
        }

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.product.activate', array(
                'id' => $this->getId()
            ))
        );

        // all fields correct?
        $this->validateFields();

        $this->active = true;

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->updateCache();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductActivate', array($this));
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
                array(
                    'userid'   => $User->getId(),
                    'username' => $User->getName()
                )
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

        $this->permissions = array();
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
            $this->permissions[$permission] = array();
        }
    }
}
