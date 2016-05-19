<?php

/**
 * This file contains QUI\ERP\Products\Product\Model
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;
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
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
            'where' => array(
                'id' => $this->getId()
            )
        ));

        if (!isset($result[0])) {
            throw new QUI\ERP\Products\Product\Exception(
                array('quiqqer/products', 'exception.product.not.found'),
                404,
                array('id' => $this->getId())
            );
        }

        $this->active = (int)$result[0]['active'] ? true : false;

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

        // main category
        $mainCategory = $this->getAttribute('category');

        if ($mainCategory !== false) {
            try {
                $this->Category = Categories::getCategory($mainCategory);
            } catch (QUI\Exception $Exception) {
            }
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
            }
        }


        // all standard and all system fields must be in the product
        $systemfields = Fields::getFields(array(
            'where_or' => array(
                'systemField' => 1,
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
     * @return ViewFrontend
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
     * @return UniqueProduct
     */
    public function createUniqueProduct()
    {
        return new UniqueProduct(
            $this->getId(),
            $this->getAttributes()
        );
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

        if (!$Category) {
            return '';
        }

        $Site = $Category->getSite();

        $url = $Site->getUrlRewritten(array(
            0 => $this->getUrlName(),
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
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false)
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

        return '#' . $this->getId();
    }

    /**
     * Return the description of the product
     *
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getDescription($Locale = false)
    {
        $result = $this->getLanguageFieldValue(
            Fields::FIELD_SHORT_DESC,
            $Locale
        );

        if ($result) {
            return $result;
        }

        QUI\System\Log::addWarning(
            QUI::getLocale()->get(
                'quiqqer/products',
                'warning.product.have.no.description',
                array('id' => $this->getId())
            ),
            array(
                'id' => $this->getId()
            )
        );

        return '#' . $this->getId();
    }

    /**
     * Return the product content
     *
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getContent($Locale = false)
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_CONTENT, $Locale);

        if ($result) {
            return $result;
        }
//
//        QUI\System\Log::addWarning(
//            QUI::getLocale()->get(
//                'quiqqer/products',
//                'warning.product.have.no.content',
//                array('id' => $this->getId())
//            ),
//            array(
//                'id' => $this->getId()
//            )
//        );

        return '';
    }

    /**
     * Return the value of an language field
     *
     * @param integer $field - optional
     * @param QUI\Locale|boolean $Locale - optional
     *
     * @return string|boolean
     */
    protected function getLanguageFieldValue($field, $Locale = false)
    {
        if (!$Locale) {
            $Locale = Products::getLocale();
        }

        $current = $Locale->getCurrent();

        try {
            $Field = $this->getField($field);
            $data  = $Field->getValue();

            if (is_string($data)) {
                return $data;
            }

            if (isset($data[$current])) {
                return $data[$current];
            }
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Return the price
     *
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        $price = $this->getFieldValue(Fields::FIELD_PRICE);

        return new QUI\ERP\Products\Utils\Price(
            $price,
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
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

        /* @var $Field Field */
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
        QUI\Rights\Permission::checkPermission('product.edit');

        $categoryFields = array();
        $categoryData   = array();
        $categories     = $this->getCategories();

        /* @var $Field Field */
        /* @var $Category Category */

        // get category field data
        foreach ($categories as $Category) {
            $categoryData[] = $Category->getId();
            $catFields      = $Category->getFields();

            foreach ($catFields as $Field) {
                $categoryFields[$Field->getId()] = true;
            }
        }


        // set main category
        $mainCategory = '';
        $Category     = $this->getCategory();

        if ($Category) {
            $mainCategory = $Category->getId();
        }

        // update
        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array(
                'categories' => ',' . implode(',', $categoryData) . ',',
                'category' => $mainCategory,
                'fieldData' => json_encode($this->validateFields())
            ),
            array('id' => $this->getId())
        );

        $this->updateCache();
    }

    /**
     * Validate the fields and return the field data
     *
     * @return array
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    protected function validateFields()
    {
        $fieldData    = array();
        $categoryData = array();

        $fields     = $this->getFields();
        $categories = $this->getCategories();

        $categoryFields = array();

        /* @var $Field Field */
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
            /* @var $Field Field */
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

        // generate the product field data
        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            $value = $Field->getValue();

            // @todo muss alle categorien prüfen
            if (!$Field->isSystem()) {
                $Field->setUnassignedStatus(
                    !isset($categoryFields[$Field->getId()])
                );
            } else {
                $Field->setUnassignedStatus(false);
            }

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
                        'id' => $Field->getId(),
                        'title' => $Field->getTitle(),
                        'fieldType' => $Field->getType()
                    )
                );

                throw new QUI\ERP\Products\Product\Exception(
                    array(
                        'quiqqer/products',
                        'exception.field.invalid',
                        array(
                            'fieldId' => $Field->getId(),
                            'fieldTitle' => $Field->getTitle(),
                            'fieldType' => $Field->getType()
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
                            'fieldId' => $Field->getId(),
                            'fieldTitle' => $Field->getTitle(),
                            'fieldType' => $Field->getType()
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
            'title' => $this->getFieldValueByLocale(
                Fields::FIELD_TITLE,
                $Locale
            ),
            'active' => $this->isActive() ? 1 : 0
        );

        $Category = $this->getCategory();

        if ($Category) {
            $data['category'] = $Category->getId();
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
            'from' => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'where' => array(
                'id' => $this->getId(),
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
                'id' => $this->getId(),
                'lang' => $lang
            )
        );
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('product.delete');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('id' => $this->getId())
        );

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            array('id' => $this->getId())
        );

        // @todo aus cache entfernen
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
        return $this->fields;
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

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->fields as $Field) {
            if ($Field->getType() == $type) {
                $result[] = $Field;
            }
        }

        return $result;
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
                    'fieldId' => $fieldId,
                    'productId' => $this->getId()
                )
            ),
            1002
        );
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
        if (is_null($this->Category)) {
            $categories = $this->getCategories();

            if (isset($categories[0])) {
                $this->Category = $categories[0];
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
     * @throws QUI\Exception
     */
    public function getMediaFolder()
    {
        $folderUrl = $this->getFieldValue(Fields::FIELD_FOLDER);
        $Folder    = MediaUtils::getMediaItemByUrl($folderUrl);

        if (MediaUtils::isFolder($Folder)) {
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
            $Project     = QUI::getRewrite()->getProject();
            $Media       = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder) {
                return $Placeholder;
            }
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
        QUI\Rights\Permission::checkPermission('product.activate');

        $this->active = false;

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('active' => 0),
            array('id' => $this->getId())
        );

        $this->updateCache();
    }

    /**
     * Activate the product
     */
    public function activate()
    {
        QUI\Rights\Permission::checkPermission('product.activate');

        // all fields correct?
        $this->validateFields();

        $this->active = true;

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('active' => 1),
            array('id' => $this->getId())
        );

        $this->updateCache();
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }
}
