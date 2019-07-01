<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantParent
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Interfaces\FieldInterface as Field;
use QUI\ERP\Products\Product\Exception;
use QUI\ERP\Products\Utils\Tables;
use QUI\ERP\Products\Handler\Fields as FieldHandler;

use QUI\ERP\Products\Field\Types\AttributeGroup;
use QUI\ERP\Products\Field\Types\ProductAttributeList;
use QUI\ERP\Products\Handler\Search as SearchHandler;

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
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     * @throws Exception
     */
    protected function productSave($fieldData)
    {
        QUI\Permissions\Permission::checkPermission('product.edit');

        $editableAttribute  = $this->getAttribute('editableVariantFields');
        $inheritedAttribute = $this->getAttribute('inheritedVariantFields');

        $data = [];

        if (\is_array($editableAttribute)) {
            $editable = [];

            // check if fields exists
            foreach ($editableAttribute as $fieldId) {
                try {
                    $editable[] = FieldHandler::getField($fieldId)->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }

            $data['editableVariantFields'] = \json_encode($editable);
        }

        if (\is_array($inheritedAttribute)) {
            $inherited = [];

            // check if fields exists
            foreach ($inheritedAttribute as $fieldId) {
                try {
                    $inherited[] = FieldHandler::getField($fieldId)->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }

            $data['inheritedVariantFields'] = \json_encode($inherited);
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

        parent::productSave($fieldData);
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
     * @param null $User
     * @return QUI\ERP\Money\Price|void
     *
     * @throws QUI\Exception
     */
    public function getMinimumPrice($User = null)
    {
        $MinPrice = null;
        $children = $this->getVariants();

        foreach ($children as $Child) {
            // at frontend, considere only active products
            if (\defined('QUIQQER_FRONTEND') && QUIQQER_FRONTEND) {
                if ($Child->isActive() === false) {
                    continue;
                }
            }

            try {
                $Price = $Child->getMinimumPrice($User);

                if ($MinPrice === null) {
                    $MinPrice = $Price;
                    continue;
                }

                if ($MinPrice->value() < $Price->value()) {
                    $MinPrice = $Price;
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        if ($MinPrice === null) {
            return parent::getMinimumPrice($User);
        }

        return $MinPrice;
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
                    $images      = \array_merge($images, $childImages);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());
                }
            }
        }

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

    /**
     * Write cache entry for product for specific language
     *
     * @param string $lang
     * @throws QUI\Exception
     */
    protected function writeCacheEntry($lang)
    {
        $Locale = new QUI\Locale();
        $Locale->setCurrent($lang);

        // wir nutzen system user als netto user
        $SystemUser = QUI::getUsers()->getSystemUser();
        $minPrice   = $this->getMinimumPrice($SystemUser)->value();
        $maxPrice   = $this->getMaximumPrice($SystemUser)->value();

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
        $type         = QUI\ERP\Products\Product\Types\Product::class;
        $productType  = $this->getAttribute('type');
        $ProductTypes = QUI\ERP\Products\Utils\ProductTypes::getInstance();

        if ($ProductTypes->exists($productType)) {
            $type = $productType;
        }

        $data = [
            'type'        => $type,
            'productNo'   => $this->getFieldValueByLocale(
                FieldHandler::FIELD_PRODUCT_NO,
                $Locale
            ),
            'title'       => $this->getFieldValueByLocale(
                FieldHandler::FIELD_TITLE,
                $Locale
            ),
            'description' => $this->getFieldValueByLocale(
                FieldHandler::FIELD_SHORT_DESC,
                $Locale
            ),
            'active'      => $this->isActive() ? 1 : 0,
            'minPrice'    => $minPrice ? $minPrice : 0,
            'maxPrice'    => $maxPrice ? $maxPrice : 0,
            'c_date'      => $cDate,
            'e_date'      => $eDate
        ];

        // permissions
        $permissions     = $this->getPermissions();
        $viewPermissions = null;

        if (isset($permissions['permission.viewable']) && !empty($permissions['permission.viewable'])) {
            $viewPermissions = ','.$permissions['permission.viewable'].',';
        }

        $data['viewUsersGroups'] = $viewPermissions;

        // get all categories
        $categories = $this->getCategories();

        if (!empty($categories)) {
            $catIds = [];

            /** @var QUI\ERP\Products\Category\Category $Category */
            foreach ($categories as $Category) {
                $catIds[] = $Category->getId();
            }

            $data['category'] = ','.\implode(',', $catIds).',';
        } else {
            $data['category'] = null;
        }

        // VariantParent fields
        $fields       = $this->getFields();
        $searchFields = [];

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            if (!$Field->isSearchable()) {
                continue;
            }

            $columnType = mb_strtolower($Field->getColumnType());

            if (mb_strpos($columnType, 'text') === false
                && mb_strpos($columnType, 'char') === false) {
                continue;
            }

            $fieldColumnName = SearchHandler::getSearchFieldColumnName($Field);
            $searchValue     = $Field->getSearchCacheValue($Locale);

            $data[$fieldColumnName] = $searchValue;

            if ($Field->getId() == FieldHandler::FIELD_PRIORITY
                && empty($data[$fieldColumnName])
            ) {
                // in 10 Jahren darf mor das fixen xD
                // null und 0 wird als letztes angezeigt
                $data[$fieldColumnName] = 999999;
            }

            $searchFields[$Field->getId()] = [
                'column' => $fieldColumnName,
                'values' => [
                    $searchValue => true
                ]
            ];
        }

        // Field values of all VariantChildren
        /**
         * If the VariantParent shall also be found when searching for values of its VariantChildren
         * the search cache entries have to include all child values as well.
         */
        if (QUI::getPackage('quiqqer/products')->getConfig()->get('variants', 'findVariantParentByChildValues')) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'fieldData'],
                'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId(),
                    'active' => 1
                ]
            ]);

            foreach ($result as $row) {
                $fields = json_decode($row['fieldData'], true);

                foreach ($fields as $fieldData) {
                    $fieldId = $fieldData['id'];
                    $value   = $fieldData['value'];

                    // Only parse children fields that are put in the search table
                    if (!isset($searchFields[$fieldId])) {
                        continue;
                    }

                    $fieldColumnName = $searchFields[$fieldId]['column'];
                    $Field           = FieldHandler::getField($fieldId);

                    // Only parse children fields that have a non-numeric (i.e. textual) search cache value
                    switch ($Field->getSearchType()) {
                        case SearchHandler::SEARCHDATATYPE_NUMERIC:
                            continue 2;
                            break;
                    }

                    $Field->setValue($value);

                    $searchValue = $Field->getSearchCacheValue($Locale);

                    // Do not add duplicate search cache values
                    if (isset($searchFields[$fieldId]['values'][$searchValue])) {
                        continue;
                    }

                    $data[$fieldColumnName] .= ' '.$searchValue;

                    $searchFields[$fieldId]['values'][$searchValue] = true;
                }
            }
        }

        // Prepare data for INSERT
        foreach ($data as $k => $v) {
            if (\is_array($v)) {
                $data[$k] = \json_encode($v);
            } elseif (\is_string($v)) {
                $data[$k] = trim($v);
            }
        }

        // test if cache entry exists first
        $result = QUI::getDataBase()->fetch([
            'from'  => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'where' => [
                'id'   => $this->getId(),
                'lang' => $lang
            ]
        ]);

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
            [
                'id'   => $this->getId(),
                'lang' => $lang
            ]
        );
    }

    //endregion

    /**
     * Return all variants
     *
     * @param array $params - query params
     * @return QUI\ERP\Products\Product\Types\VariantChild[]|integer
     *
     * @todo cache
     */
    public function getVariants($params = [])
    {
        if ($this->children !== null) {
            if (isset($params['count'])) {
                return \count($this->children);
            }

            return $this->children;
        }

        try {
            $query = [
                'select' => ['id', 'parent'],
                'from'   => Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId()
                ]
            ];

            if (isset($params['limit'])) {
                $query['limit'] = $params['limit'];
            }

            if (isset($params['order'])) {
                switch (\mb_strtolower($params['order'])) {
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
            }

            $result = QUI::getDataBase()->fetch($query);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        if (isset($params['count'])) {
            return (int)$result[0]['count'];
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
    public function getVariantByVariantHash($hash)
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id, variantHash',
                'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where'  => [
                    'variantHash' => $hash
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
    public function generateVariants($fields = [], $generationType = self::GENERATION_TYPE_RESET)
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

        foreach ($fields as $entry) {
            if (empty($entry['fieldId'])) {
                continue;
            }

            // only group lists can be permutated
            try {
                $Field = FieldHandler::getField($entry['fieldId']);

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

            $group   = [];
            $fieldId = $entry['fieldId'];
            $values  = $entry['values'];

            foreach ($values as $value) {
                $group[] = ['fieldId' => $fieldId, 'value' => $value];
            }

            if (!empty($group)) {
                $list[] = $group;
            }
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

            $this->generateVariant($fields);
        }
    }

    /**
     * Generate permutation array (all combinations) from a php array list
     *
     * @param array $lists
     * @return array
     */
    protected function permutations(array $lists)
    {
        $permutations = [];
        $iter         = 0;

        while (true) {
            $num  = $iter++;
            $pick = [];

            foreach ($lists as $l) {
                $r      = $num % \count($l);
                $num    = ($num - $r) / \count($l);
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
    public function generateVariant($fields)
    {
        Products::disableGlobalWriteProductDataToDb();

        $Variant = $this->createVariant();

        // set fields
        foreach ($fields as $field => $value) {
            try {
                $Field = FieldHandler::getField($field);
            } catch (QUI\Exception $Exception) {
                continue;
            }

            // add only attribute groups
            if ($Field->getType() === FieldHandler::TYPE_ATTRIBUTE_GROUPS) {
                $Variant->addField($Field);

                try {
                    $Variant->getField($field)->setValue($value);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());
                }

                continue;
            }

            if ($Field->getType() === FieldHandler::TYPE_ATTRIBUTE_LIST) {
                $Variant->addField($Field);
            }
        }

        // set article no
        $parentProductNo = $this->getFieldValue(FieldHandler::FIELD_PRODUCT_NO);
        $newNumber       = \count($this->getVariants()) + 1;

        $Variant->getField(FieldHandler::FIELD_PRODUCT_NO)->setValue(
            $parentProductNo.'-'.$newNumber
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

        $attributes = $Variant->getFieldsByType([
            FieldHandler::TYPE_ATTRIBUTE_GROUPS
        ]);

        /* @var $Field QUI\ERP\Products\Field\Field */
        $newValues = [];

        // first add titles to the url

        // second add fields to the url
        foreach ($attributes as $Field) {
            foreach ($urlValue as $lang => $value) {
                $LocaleClone->setCurrent($lang);

                $title = $Field->getTitle($LocaleClone);
                $value = $Field->getValueByLocale($LocaleClone);

                $newValues[$lang][] = QUI\Projects\Site\Utils::clearUrl($title.'-'.$value);
            }
        }

        foreach ($urlValue as $lang => $value) {
            $LocaleClone->setCurrent($lang);
            $productTitle = $this->getTitle($LocaleClone);
            $productTitle = QUI\Projects\Site\Utils::clearUrl($productTitle);

            $productSuffix = \implode('-', $newValues[$lang]);
            $productSuffix = \trim($productSuffix, '-');

            $urlValue[$lang] = $productTitle.'-'.$productSuffix;
        }


        $this->calcVariantPrice($Variant, $fields);

        Products::enableGlobalWriteProductDataToDb();

        $URL->setValue($urlValue);
        $Variant->save();

        return $Variant;
    }

    /**
     * Calculates the price of an variant children
     * - looks if attribute lists change the price
     *
     * @param VariantChild $Variant
     * @param $fields
     */
    protected function calcVariantPrice(VariantChild $Variant, $fields)
    {
        // @todo Implement when there are surcharges and discounts for group lists
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
    public function validateFields()
    {
        $fields = $this->getAllProductFields();

        foreach ($fields as $Field) {
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
    public function availableChildFields()
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
    public function availableActiveChildFields()
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => 'id, parent, fieldData, variantHash',
                'from'   => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId(),
                    'active' => 1
                ]
            ]);
        } catch (QUI\Exception $Exception) {
            $result = [];
        }

        return $this->parseAvailableFields($result);
    }

    /**
     * @param array $result
     * @return array
     */
    protected function parseAvailableFields($result)
    {
        $fields = [];

        foreach ($result as $entry) {
            $fieldData     = \json_decode($entry['fieldData'], true);
            $variantFields = [];

            foreach ($fieldData as $field) {
                $variantFields[$field['id']] = $field['value'];
            }

            $variantHash = $entry['variantHash'];
            $variantHash = \trim($variantHash, ';');
            $variantHash = \explode(';', $variantHash);

            foreach ($variantHash as $fieldHash) {
                $fieldHash = \explode(':', $fieldHash);
                $fieldId   = (int)$fieldHash[0];

                if (isset($variantFields[$fieldId])) {
                    $fields[$fieldId][] = $variantFields[$fieldId];
                    $fields[$fieldId]   = \array_unique($fields[$fieldId]);
                }
            }
        }

        return $fields;
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
    public function isFieldAvailable($fieldId, $fieldValue)
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
    public function hasVariantId($variantId)
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
    public function setDefaultVariant($variantId)
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
