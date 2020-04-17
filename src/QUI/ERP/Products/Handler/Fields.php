<?php

/**
 * This file contains QUI\ERP\Products\Handler\Fields
 */

namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Fields
 *
 * @package QUI\ERP\Products\Handler
 *
 * Feld Rechte:
 * - permission.products.fields.field{$newId}.edit
 * - permission.products.fields.field{$newId}.view
 */
class Fields
{
    /**
     * Fields
     */
    const FIELD_PRICE            = 1;
    const FIELD_VAT              = 2;
    const FIELD_PRODUCT_NO       = 3;
    const FIELD_TITLE            = 4;
    const FIELD_SHORT_DESC       = 5;
    const FIELD_CONTENT          = 6;
    const FIELD_SUPPLIER         = 7;
    const FIELD_MANUFACTURER     = 8;
    const FIELD_IMAGE            = 9; // Main product image
    const FIELD_FOLDER           = 10; // Main media folder
    const FIELD_KEYWORDS         = 13;
    const FIELD_EQUIPMENT        = 14;
    const FIELD_SIMILAR_PRODUCTS = 15;
    const FIELD_PRICE_OFFER      = 16; // angebotspreis
    const FIELD_PRICE_RETAIL     = 17; // UVP - RRP
    const FIELD_PRIORITY         = 18; // Product Priority
    const FIELD_URL              = 19; // Product URL
    const FIELD_UNIT             = 20;
    const FIELD_EAN              = 21;
    const FIELD_WEIGHT           = 22;

    /**
     * Types
     */
    const TYPE_BOOL                = 'BoolType';
    const TYPE_DATE                = 'Date';
    const TYPE_FLOAT               = 'FloatType';
    const TYPE_FOLDER              = 'Folder';
    const TYPE_GROUP_LIST          = 'GroupList';
    const TYPE_IMAGE               = 'Image';
    const TYPE_INPUT               = 'Input';
    const TYPE_INPUT_MULTI_LANG    = 'InputMultiLang';
    const TYPE_INT                 = 'IntType';
    const TYPE_PRICE               = 'Price';
    const TYPE_PRICE_BY_QUANTITY   = 'PriceByQuantity';
    const TYPE_PRICE_BY_TIMEPERIOD = 'PriceByTimePeriod';
    const TYPE_TEXTAREA            = 'Textarea';
    const TYPE_TEXTAREA_MULTI_LANG = 'TextareaMultiLang';
    const TYPE_URL                 = 'Url';
    const TYPE_VAT                 = 'Vat';
    const TYPE_TAX                 = 'Tax';
    const TYPE_PRODCUCTS           = 'Products';
    const TYPE_UNITSELECT          = 'UnitSelect';
    const TYPE_TIMEPERIOD          = 'TimePeriod';
    const TYPE_CHECKBOX_INPUT      = 'CheckboxInput';

    const TYPE_ATTRIBUTES       = 'AttributeGroup';
    const TYPE_ATTRIBUTE_GROUPS = 'AttributeGroup';
    const TYPE_ATTRIBUTE_LIST   = 'ProductAttributeList';

    /**
     * product array changed types
     */
    const PRODUCT_ARRAY_CHANGED   = 'pac'; // product array has changed
    const PRODUCT_ARRAY_UNCHANGED = 'pau'; // product array hasn't changed

    /**
     * List of cache names
     *
     * @var array
     */
    protected static $cacheNames = [
        'quiqqer/products/fields',
        'quiqqer/products/fields/field/',
        'quiqqer/products/fields/query/'
    ];

    /**
     * @var array
     */
    protected static $list = [];

    /**
     * @var null
     */
    protected static $fieldTypes = null;

    /**
     * @var array
     */
    protected static $fieldTypeData = [];

    /**
     * Return the child attributes
     *
     * @return array
     */
    public static function getChildAttributes()
    {
        return [
            'name',
            'type',
            'search_type',
            'prefix',
            'suffix',
            'priority',
            'standardField',
            'systemField',
            'requiredField',
            'publicField',
            'options',
//            'workingtitles',
//            'titles'
        ];
    }

    /**
     * Return all standard fields
     *
     * @return array
     */
    public static function getStandardFields()
    {
        return self::getFields([
            'where' => [
                'standardField' => 1
            ]
        ]);
    }

    /**
     * Return all system fields
     *
     * @return array
     */
    public static function getSystemFields()
    {
        return self::getFields([
            'where' => [
                'systemField' => 1
            ]
        ]);
    }

    /**
     * Clear the field cache
     */
    public static function clearCache()
    {
        foreach (self::$cacheNames as $cache) {
            QUI\Cache\LongTermCache::clear($cache);
        }

        try {
            QUI::getEvents()->fireEvent('onQuiqqerProductsFieldsClearCache');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Is the mixed a field?
     *
     * @param mixed $mixed
     * @return bool
     */
    public static function isField($mixed)
    {
        if (!\is_object($mixed)) {
            return false;
        }

        if (\get_class($mixed) === QUI\ERP\Products\Field\Field::class) {
            return true;
        }

        if (\get_class($mixed) === QUI\ERP\Products\Field\UniqueField::class) {
            return true;
        }

        return $mixed instanceof QUI\ERP\Products\Interfaces\FieldInterface;
    }

    /**
     * Create a new field
     *
     * @param array $attributes - field attributes
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws \Exception
     */
    public static function createField($attributes = [])
    {
        QUI\Permissions\Permission::checkPermission('field.create');

        $data          = [];
        $allowedFields = self::getChildAttributes();

        foreach ($allowedFields as $allowed) {
            if (isset($attributes[$allowed])) {
                $data[$allowed] = $attributes[$allowed];
            }
        }

        if (!isset($data['type'])) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.fields.type.not.allowed'
            ]);
        }

        $isAllowed = self::getFieldTypeData($data['type']);

        if (empty($isAllowed)) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.fields.type.not.allowed'
            ]);
        }

        // cache colum check
        $columns = QUI::getDataBase()->table()->getColumns(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName()
        );

        if (\count($columns) > 1000) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.products.column.maxSize'
            ]);
        }

        // id checking
        if (isset($attributes['id'])) {
            $result = QUI::getDataBase()->fetch([
                'from'  => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => [
                    'id' => $attributes['id']
                ]
            ]);

            if (isset($result[0])) {
                throw new QUI\ERP\Products\Field\Exception([
                    'quiqqer/products',
                    'exception.id.already.exists'
                ]);
            }

            $data['id'] = $attributes['id'];
        } else {
            // exist an id with 1000? field-id begin at 1000
            $result = QUI::getDataBase()->fetch([
                'from'  => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => [
                    'id' => [
                        'type'  => '>=',
                        'value' => 1000
                    ]
                ],
                'limit' => 1
            ]);

            if (!isset($result[0])) {
                $data['id'] = 1000;
            }
        }

        if (isset($data['options']) && \is_array($data['options'])) {
            $data['options'] = \json_encode($data['options']);
        }

        if (!isset($data['priority']) || empty($data['priority'])) {
            $data['priority'] = 0;
        }

        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = '';
        }

        // insert field data
        QUI::getDataBase()->insert(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data
        );

        if (isset($data['id'])) {
            $newId = $data['id'];
        } else {
            $newId = QUI::getDataBase()->getPDO()->lastInsertId();
        }


        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.fields.create', [
                'id' => $newId
            ]),
            '',
            $data
        );

        // add language var, if not exists
        self::setFieldTranslations($newId, $attributes);

        $Field = self::getField($newId);


        // create view permission
        QUI::getPermissionManager()->addPermission([
            'name'  => "permission.products.fields.field{$newId}.view",
            'title' => "quiqqer/products permission.products.fields.field{$newId}.view.title",
            'desc'  => "",
            'type'  => 'bool',
            'area'  => 'groups',
            'src'   => 'user'
        ]);

        // create edit permission
        QUI::getPermissionManager()->addPermission([
            'name'  => "permission.products.fields.field{$newId}.edit",
            'title' => "quiqqer/products permission.products.fields.field{$newId}.edit.title",
            'desc'  => "",
            'type'  => 'bool',
            'area'  => 'groups',
            'src'   => 'user'
        ]);


        // create new cache column and set default search type
        if ($Field->isSearchable()) {
            self::createFieldCacheColumn($newId);
            $Field->setAttribute('search_type', $Field->getDefaultSearchType());
            $Field->save();
        }

        // clear the field cache
        QUI\Cache\LongTermCache::clear('quiqqer/products/fields');

        QUI::getEvents()->fireEvent('onQuiqqerProductsFieldCreate', [$Field]);

        return $Field;
    }

    /**
     * @param string $columnName
     * @param string $columnType - default = text
     * @throws \Exception
     */
    public static function createCacheColumn($columnName, $columnType = 'text')
    {
        QUI::getDataBase()->table()->addColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            [$columnName => $columnType]
        );
    }

    /**
     * Create cache table column for a field
     *
     * @param integer $fieldId
     * @throws \Exception
     */
    public static function createFieldCacheColumn($fieldId)
    {
        $Field = self::getField($fieldId);

        if (!$Field->isSearchable()) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.cache.column.not.allowed',
                [
                    'fieldId'    => $fieldId,
                    'fieldTitle' => $Field->getTitle()
                ]
            ]);
        }

        self::createCacheColumn(
            Search::getSearchFieldColumnName($Field),
            $Field->getColumnType()
        );
    }

    /**
     * Set the field translations of a field
     * but only if there is no translation
     *
     * @param $fieldId
     * @param $attributes
     */
    public static function setFieldTranslations($fieldId, $attributes)
    {
        $localeGroup = 'quiqqer/products';

        if (!isset($attributes['titles'])) {
            $attributes['titles'] = [];
        }

        if (!isset($attributes['workingtitles'])) {
            $attributes['workingtitles'] = [];
        }

        // title
        self::insertTranslations(
            $localeGroup,
            'products.field.'.$fieldId.'.title',
            $attributes['titles']
        );

        // working title
        self::insertTranslations(
            $localeGroup,
            'products.field.'.$fieldId.'.workingtitle',
            $attributes['workingtitles']
        );


        // permission translations
        $languages = QUI\Translator::langs();

        $headerTranslations = [];
        $viewTranslations   = [];
        $editTranslations   = [];

        foreach ($languages as $lang) {
            $title = $fieldId;

            if (isset($attributes['titles'][$lang])) {
                $title = $attributes['titles'][$lang];
            }

            $headerTranslations[$lang] = QUI::getLocale()->getByLang(
                $lang,
                'quiqqer/products',
                'quiqqer.products.field.header.placeholder',
                [
                    'fielId'    => $fieldId,
                    'fieldname' => $title
                ]
            );

            $viewTranslations[$lang] = QUI::getLocale()->getByLang(
                $lang,
                'quiqqer/products',
                'quiqqer.products.field.view.placeholder',
                [
                    'fielId'    => $fieldId,
                    'fieldname' => $title
                ]
            );

            $editTranslations[$lang] = QUI::getLocale()->getByLang(
                $lang,
                'quiqqer/products',
                'quiqqer.products.field.edit.placeholder',
                [
                    'fielId'    => $fieldId,
                    'fieldname' => $title
                ]
            );
        }

        // header
        self::insertTranslations(
            $localeGroup,
            "permission.permission.products.fields.field{$fieldId}._header",
            $headerTranslations
        );

        // view permission
        self::insertTranslations(
            $localeGroup,
            "permission.products.fields.field{$fieldId}.view.title",
            $viewTranslations
        );


        // edit permission
        self::insertTranslations(
            $localeGroup,
            "permission.products.fields.field{$fieldId}.edit.title",
            $editTranslations
        );
    }

    /**
     * Insert translations
     *
     * @param string $group
     * @param string $var
     * @param array $data
     */
    protected static function insertTranslations($group, $var, $data = [])
    {
        try {
            $translations = QUI\Translator::get($group, $var);

            if (!\is_array($data)) {
                $data = [];
            }

            $data['package']  = 'quiqqer/products';
            $data['datatype'] = 'php,js';
            $data['html']     = 1;

            if (!isset($translations[0])) {
                QUI\Translator::addUserVar($group, $var, $data);
            } else {
                QUI\Translator::edit($group, $var, 'quiqqer/products', $data);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addNotice($Exception->getMessage(), [
                'trace' => $Exception->getTrace()
            ]);
        }
    }

    /**
     * Return the cachename of a field
     *
     * @param $fieldId
     * @return string
     */
    public static function getFieldCacheName($fieldId)
    {
        return 'quiqqer/products/fields/field/'.$fieldId.'/';
    }

    /**
     * Return all available Fields
     *
     * @return array
     */
    public static function getFieldTypes()
    {
        if (self::$fieldTypes !== null) {
            return self::$fieldTypes;
        }

        $cacheName = Cache::getBasicCachePath().'fields';

        try {
            self::$fieldTypes = QUI\Cache\LongTermCache::get($cacheName);

            return self::$fieldTypes;
        } catch (QUI\Exception $Exception) {
        }

        // exists the type?
        $dir    = \dirname(\dirname(__FILE__)).'/Field/Types/';
        $files  = QUI\Utils\System\File::readDir($dir);
        $result = [];

        foreach ($files as $file) {
            if (\strpos($file, 'View') !== false) {
                continue;
            }

            $file = \pathinfo($file);

            $result[] = [
                'plugin'   => 'quiqqer/products',
                'src'      => 'QUI\ERP\Products\Field\Types\\'.$file['filename'],
                'category' => 0,
                'locale'   => ['quiqqer/products', 'fieldtype.'.$file['filename']],
                'name'     => $file['filename']
            ];
        }

        $plugins = QUI::getPackageManager()->getInstalled();

        foreach ($plugins as $plugin) {
            $xml = OPT_DIR.$plugin['name'].'/products.xml';

            if (!\file_exists($xml)) {
                continue;
            }

            $Dom  = QUI\Utils\Text\XML::getDomFromXml($xml);
            $Path = new \DOMXPath($Dom);

            $fields = $Path->query("//quiqqer/products/fields/field");

            /* @var $Field \DOMElement */
            foreach ($fields as $Field) {
                $src      = $Field->getAttribute('src');
                $category = $Field->getAttribute('category');
                $name     = $Field->getAttribute('name');
                $help     = true;

                if (!\class_exists($src)) {
                    continue;
                }

                $Help = $Field->getElementsByTagName('help');

                if ($Help->length) {
                    $Help = $Help->item(0);
                    $help = QUI\Utils\DOM::getTextFromNode($Help, false);
                }

                $result[] = [
                    'plugin'   => $plugin['name'],
                    'src'      => $src,
                    'category' => $category,
                    'locale'   => QUI\Utils\DOM::getTextFromNode($Field, false),
                    'name'     => $name,
                    'help'     => $help
                ];
            }
        }

        QUI\Cache\LongTermCache::set($cacheName, $result);
        self::$fieldTypes = $result;

        return $result;
    }

    /**
     * Return internal field init data for a field type
     *
     * @param string $type - field type
     * @return array
     */
    public static function getFieldTypeData($type)
    {
        if (isset(self::$fieldTypeData[$type])) {
            return self::$fieldTypeData[$type];
        }

        $cacheName = Cache::getBasicCachePath().'fields/'.\md5($type);

        try {
            self::$fieldTypeData[$type] = QUI\Cache\LongTermCache::get($cacheName);

            return self::$fieldTypeData[$type];
        } catch (QUI\Exception $Exception) {
        }

        $types = self::getFieldTypes();
        $found = \array_filter($types, function ($entry) use ($type) {
            return $entry['name'] == $type;
        });

        if (empty($found)) {
            self::$fieldTypeData[$type] = [];

            return [];
        }

        self::$fieldTypeData[$type] = \reset($found);

        QUI\Cache\LongTermCache::set($cacheName, self::$fieldTypeData[$type]);

        return self::$fieldTypeData[$type];
    }

    /**
     * Return a field
     *
     * @param string $type - wanted field type
     * @param integer $fieldId - ID of the field
     * @param array $fieldParams - optional,  Params of the field
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws QUI\Exception
     */
    public static function getFieldByType(
        $type,
        $fieldId,
        $fieldParams = []
    ) {
        $class = 'QUI\ERP\Products\Field\Types\\'.$type;

        if (\class_exists($class)) {
            return new $class($fieldId, $fieldParams);
        }

        throw new QUI\ERP\Products\Field\Exception([
            'quiqqer/products',
            'exception.field.type_not_found',
            [
                'fieldType' => $type,
                'fieldId'   => $fieldId
            ]
        ]);
    }

    /**
     * Return a field
     *
     * @param integer $fieldId - Field-ID
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws QUI\ERP\Products\Field\Exception
     */
    public static function getField($fieldId)
    {
        if (isset(self::$list[$fieldId])) {
            return clone self::$list[$fieldId];
        }

        // Wenn der RAM zu voll wird, Objekte mal leeren
        if (QUI\Utils\System::memUsageToHigh()) {
            self::$list = [];
        }

        try {
            $data = QUI\Cache\LongTermCache::get(
                QUI\ERP\Products\Handler\Fields::getFieldCacheName($fieldId)
            );
        } catch (QUI\Exception $Exception) {
            try {
                $result = QUI::getDataBase()->fetch([
                    'from'  => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                    'where' => [
                        'id' => (int)$fieldId
                    ],
                    'limit' => 1
                ]);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                $result = false;
            }

            if (!$result || !isset($result[0])) {
                throw new QUI\ERP\Products\Field\Exception(
                    [
                        'quiqqer/products',
                        'exception.field.id_not_found',
                        [
                            'id'   => $fieldId,
                            'type' => ''
                        ]
                    ],
                    404,
                    ['fieldId' => (int)$fieldId]
                );
            }

            $data = $result[0];
        }


        // exists the type?
        $fieldTypes = self::getFieldTypeData($data['type']);

        if (!isset($fieldTypes['src'])) {
            $fieldTypes['src'] = '';
        }

        $class = $fieldTypes['src'];

        if (empty($fieldTypes)) {
            throw new QUI\ERP\Products\Field\Exception(
                [
                    'quiqqer/products',
                    'exception.field.type.not.found',
                    [
                        'id'   => $fieldId,
                        'type' => ''
                    ]
                ],
                404,
                [
                    'id'   => (int)$fieldId,
                    'type' => $data['type']
                ]
            );
        }

        if (!\class_exists($class)) {
            throw new QUI\ERP\Products\Field\Exception(
                [
                    'quiqqer/products',
                    'exception.field.class.not.found',
                    [
                        'id'   => $fieldId,
                        'type' => ''
                    ]
                ],
                404,
                [
                    'id'    => (int)$fieldId,
                    'type'  => $data['type'],
                    'class' => $class
                ]
            );
        }

        $fieldData = [
            'system'       => (int)$data['systemField'],
            'required'     => (int)$data['requiredField'],
            'standard'     => (int)$data['standardField'],
            'defaultValue' => \json_decode($data['defaultValue'], true)
        ];

        /* @var $Field QUI\ERP\Products\Field\Field */
        $Field = new $class($fieldId, $fieldData);

        if (!QUI\ERP\Products\Utils\Fields::isField($Field)) {
            throw new QUI\ERP\Products\Field\Exception(
                [
                    'quiqqer/products',
                    'exception.field.is.no.field',
                    [
                        'id'   => $fieldId,
                        'type' => ''
                    ]
                ],
                404,
                [
                    'id'    => (int)$fieldId,
                    'type'  => $data['type'],
                    'class' => $class
                ]
            );
        }

        $Field->setAttributes($data);

        if (empty($data['priority'])) {
            $data['priority'] = 0;
        }

        if (empty($data['prefix'])) {
            $data['prefix'] = '';
        }

        if (empty($data['suffix'])) {
            $data['suffix'] = '';
        }

        if (empty($data['options'])) {
            $data['options'] = '';
        }

        $Field->setAttribute('priority', $data['priority']);
        $Field->setAttribute('prefix', $data['prefix']);
        $Field->setAttribute('suffix', $data['suffix']);
        $Field->setOptions($data['options']);

        self::$list[$fieldId] = clone $Field;

        return $Field;
    }

    /**
     * Return a list of field ids
     * if $queryParams is empty, all fields are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     * @return array
     */
    public static function getFieldIds($queryParams = [])
    {
        $cacheName = Cache::getBasicCachePath().'query/'.\md5(\serialize($queryParams));

        try {
            return QUI\Cache\LongTermCache::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }

        $query = [
            'select' => 'id',
            'from'   => QUI\ERP\Products\Utils\Tables::getFieldTableName()
        ];

        if (isset($queryParams['where'])) {
            $query['where'] = $queryParams['where'];
        }

        if (isset($queryParams['where_or'])) {
            $query['where_or'] = $queryParams['where_or'];
        }

        if (isset($queryParams['limit'])) {
            $query['limit'] = $queryParams['limit'];
        }

        if (!isset($queryParams['order'])) {
            $queryParams['order'] = 'priority ASC';
        }

        switch ($queryParams['order']) { // bad solution
            case 'id':
            case 'id ASC':
            case 'id DESC':
            case 'name':
            case 'name ASC':
            case 'name DESC':
            case 'type':
            case 'type ASC':
            case 'type DESC':
            case 'search_type':
            case 'search_type ASC':
            case 'search_type DESC':
            case 'prefix':
            case 'prefix ASC':
            case 'prefix DESC':
            case 'suffix':
            case 'suffix ASC':
            case 'suffix DESC':
            case 'priority':
            case 'priority ASC':
            case 'priority DESC':
            case 'standardField':
            case 'standardField ASC':
            case 'standardField DESC':
            case 'systemField':
            case 'systemField ASC':
            case 'systemField DESC':
            case 'requiredField':
            case 'requiredField ASC':
            case 'requiredField DESC':
                $query['order'] = $queryParams['order'];
                break;

            default:
                $query['order'] = 'priority ASC, id ASC';
        }

        //$query['debug'] = true;

        try {
            $result = QUI::getDataBase()->fetch($query);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }


        try {
            QUI\Cache\LongTermCache::set($cacheName, $result);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $result;
    }

    /**
     * Return a list of fields
     * if $queryParams is empty, all fields are returned
     *
     * @param array $queryParams - query parameter
     *                              $queryParams['where'],
     *                              $queryParams['where_or'],
     *                              $queryParams['limit']
     *                              $queryParams['order']
     *
     * @return QUI\ERP\Products\Interfaces\FieldInterface[]
     */
    public static function getFields($queryParams = [])
    {
        $result = [];
        $data   = self::getFieldIds($queryParams);

        foreach ($data as $entry) {
            try {
                $result[] = self::getField($entry['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addNotice($Exception->getMessage());

                if (DEVELOPMENT || DEBUG_MODE) {
                    QUI\System\Log::writeDebugException(
                        $Exception,
                        QUI\System\Log::LEVEL_NOTICE,
                        $Exception->getContext()
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Return all fields by a specific type
     *
     * @param $type
     * @return QUI\ERP\Products\Interfaces\FieldInterface[]
     */
    public static function getFieldsByType($type)
    {
        $result = [];
        $fields = self::getFields();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($fields as $Field) {
            if ($Field->getType() == $type) {
                $result[] = $Field;
            }
        }

        return QUI\ERP\Products\Utils\Fields::sortFields($result);
    }

    /**
     * Return the number of the fields
     *
     * @param array $queryParams - query params (where, where_or)
     * @return integer
     */
    public static function countFields($queryParams = [])
    {
        $query = [
            'from'  => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
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
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());

            return 0;
        }

        if (isset($data[0]) && isset($data[0]['count'])) {
            return (int)$data[0]['count'];
        }

        return 0;
    }

    /**
     * Set system attributes of all fields to all products that have these fields.
     *
     * This overwrites custom settings some products may have for individual fields.
     *
     * Attributes included:
     * - isPublic
     * - showInDetails
     *
     * @param int $fieldId (optional) - Restrict to one field [default: all fields]
     * @param array $customaAttributes (optional) - Set custom attributes that are set to
     * every product field
     * @return void
     */
    public static function setFieldAttributesToProducts($fieldId = null, $customaAttributes = [])
    {
        if (!empty($fieldId)) {
            $fieldIds = self::getFieldIds([
                'where' => [
                    'id' => $fieldId
                ]
            ]);
        } else {
            $fieldIds = self::getFieldIds();
        }

        // Collect field attributes
        $fieldAttributes = [];

        foreach ($fieldIds as $row) {
            $fieldId = $row['id'];

            try {
                $Field = self::getField($fieldId);

                $fieldAttributes[$fieldId] = [
                    'isPublic'      => $Field->isPublic(),
                    'showInDetails' => $Field->showInDetails()
                ];
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Disable certain product operations for better performance
        Products::disableGlobalFireEventsOnProductSave();
        Products::disableGlobalProductSearchCacheUpdate();

        $productIds = Products::getProductIds();

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getNewProductInstance($productId);

                foreach ($fieldAttributes as $fieldId => $attributes) {
                    if (!$Product->hasField($fieldId)) {
                        continue;
                    }

                    try {
                        $ProductField = $Product->getField($fieldId);
                        $ProductField->setPublicStatus($attributes['isPublic']);
                        $ProductField->setShowInDetailsStatus($attributes['showInDetails']);

                        foreach ($customaAttributes as $k => $v) {
                            switch ($k) {
                                case 'ownField':
                                    $ProductField->setOwnFieldStatus($v);
                                    break;

                                case 'unassigned':
                                    $ProductField->setUnassignedStatus($v);
                                    break;

                                default:
                                    $ProductField->setAttribute($k, $v);
                            }
                        }

                        $Product->save();
                    } catch (\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);
                        continue;
                    }

                    Products::cleanProductInstanceMemCache();
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                continue;
            }
        }

        // Re-enable disabled product operations
        Products::enableGlobalFireEventsOnProductSave();
        Products::enableGlobalProductSearchCacheUpdate();
    }
}
