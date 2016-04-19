<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\Field;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Handler\Categories;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\Security\Orthos;

/**
 * Class Controller
 * Product Model
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
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
     * Model constructor
     *
     * @param integer $pid - Product-ID
     *
     * @throws QUI\Exception
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
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.product.not.found'),
                404,
                array('id' => $this->getId())
            );
        }

        unset($result[0]['id']);

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

                $this->fields[$Field->getId()] = $Field;

            } catch (QUI\Exception $Exception) {
            }
        }


        // all standard system fields must be in the product

        // field list from the system
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
        return new ViewFrontend($this->createUniqueProduct());
    }

    /**
     * @return ViewBackend
     */
    public function getViewBackend()
    {
        return new ViewBackend($this->createUniqueProduct());
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
        $result = $this->getLanguageFieldValue(Fields::FIELD_SHORT_DESC,
            $Locale);

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
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getContent($Locale = false)
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_CONTENT, $Locale);

        if ($result) {
            return $result;
        }

        QUI\System\Log::addWarning(
            QUI::getLocale()->get(
                'quiqqer/products',
                'warning.product.have.no.content',
                array('id' => $this->getId())
            ),
            array(
                'id' => $this->getId()
            )
        );

        return '#' . $this->getId();
    }

    /**
     * Return the value of an language field
     *
     * @param integer $field - optional
     * @param QUI\Locale|Boolean $Locale - optional
     *
     * @return string|boolean
     */
    protected function getLanguageFieldValue($field, $Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
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
        $attributes       = parent::getAttributes();
        $attributes['id'] = $this->getId();

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
            $fields[] = $Field->toProductArray();
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
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('product.edit');

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
        foreach ($fields as $Field) {
            $value = $Field->getValue();

            \QUI\System\Log::writeRecursive($Field->getId());
            \QUI\System\Log::writeRecursive($value);

            // @todo muss alle categorien prüfen
            if (!$Field->isSystem()) {
                $Field->setUnassignedStatus(
                    !isset($categoryFields[$Field->getId()])
                );
            } else {
                $Field->setUnassignedStatus(false);
            }

            if (!$Field->isRequired()) {
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
                        'id'    => $Field->getId(),
                        'title' => $Field->getTitle()
                    )
                );

                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId'    => $Field->getId(),
                        'fieldTitle' => $Field->getTitle(),
                        'fieldType'  => $Field->getType()
                    )
                ));
            }


            if ($value === '') {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId'    => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType'  => $this->getType()
                    )
                ));
            }

            $fieldData[] = $Field->toProductArray();

            \QUI\System\Log::writeRecursive("don");
        }

        // set main category
        $mainCategory = '';
        $Category     = $this->getCategory();

        if ($Category) {
            $mainCategory = $Category->getId();
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array(
                'categories' => ',' . implode(',', $categoryData) . ',',
                'category'   => $mainCategory,
                'fieldData'  => json_encode($fieldData)
            ),
            array('id' => $this->getId())
        );

        $this->updateCache();
    }

    /**
     * Updates the cache table with current product data
     *
     * @return void
     * @throws QUI\Exception
     */
    protected function updateCache()
    {
        $data = array(
            'id'        => $this->getId(),
            'productNo' => $this->getFieldValue(Fields::FIELD_PRODUCT_NO),
            'title'     => $this->getFieldValue(Fields::FIELD_TITLE)
        );

        $Category = $this->getCategory();

        if ($Category) {
            $data['category'] = $Category->getId();
        }

        $fields = $this->getFields();

        /** @var Field $Field */
        foreach ($fields as $Field) {
            $data['F' . $Field->getId()] = $Field->getValue();
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            $data,
            array(
                'id' => $this->getId()
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
     * @return Field
     * @throws QUI\Exception
     */
    public function getField($fieldId)
    {
        if (isset($this->fields[$fieldId])) {
            return $this->fields[$fieldId];
        }

        throw new QUI\Exception(array(
            'quiqqer/products',
            'exception.field.not.found',
            array(
                'fieldId'   => $fieldId,
                'productId' => $this->getId()
            )
        ));
    }

    /**
     * Return the field value
     *
     * @param integer $fieldId
     * @return mixed
     * @throws QUI\Exception
     */
    public function getFieldValue($fieldId)
    {
        return $this->getField($fieldId)->getValue();
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

        throw new QUI\Exception(array(
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

        throw new QUI\Exception(array(
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
}
