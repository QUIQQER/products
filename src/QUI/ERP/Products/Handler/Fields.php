<?php

/**
 * This file contains QUI\ERP\Products\Handler\Fields
 */
namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Fields
 * @package QUI\ERP\Products\Handler
 */
class Fields
{
    const FIELD_PRICE = 1;

    const FIELD_TAX = 2;

    const FIELD_PRODUCT_NO = 3;

    const FIELD_TITLE = 4;

    const FIELD_SHORT_DESC = 5;

    const FIELD_CONTENT = 6;

    const FIELD_SUPPLIER = 7;

    const FIELD_MANUFACTURER = 8;

    const FIELD_IMAGE = 9;

    const FIELD_FOLDER = 10;

    /**
     * @var array
     */
    protected static $list = array();

    /**
     * Return the child attributes
     *
     * @return array
     */
    public static function getChildAttributes()
    {
        return array(
            'name',
            'type',
            'search_type',
            'prefix',
            'suffix',
            'priority',
            'standardField',
            'systemField',
            'requiredField',
            'options'
        );
    }

    /**
     * Return all standard fields
     *
     * @return array
     */
    public function getStandardFields()
    {
        return $this->getFields(array(
            'where' => array(
                'standardField' => 1
            )
        ));
    }

    /**
     * Return all system fields
     *
     * @return array
     */
    public function getSystemFields()
    {
        return $this->getFields(array(
            'where' => array(
                'systemField' => 1
            )
        ));
    }

    /**
     * Create a new field
     *
     * @param array $attributes - field attributes
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws QUI\Exception
     */
    public static function createField($attributes = array())
    {
        QUI\Rights\Permission::checkPermission('field.create');

        $data          = array();
        $allowedFields = self::getChildAttributes();
        $allowedTypes  = self::getFieldTypes();

        foreach ($allowedFields as $allowed) {
            if (isset($attributes[$allowed])) {
                $data[$allowed] = $attributes[$allowed];
            }
        }


        if (!isset($data['type'])) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.fields.type.not.allowed'
            ));
        }

        if (!in_array($data['type'], $allowedTypes)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.fields.type.not.allowed'
            ));
        }

        // cache colum check
        $columns = QUI::getDataBase()->table()->getColumns(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName()
        );

        if (count($columns) > 1000) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.products.column.maxSize'
            ));
        }

        // id checking
        if (isset($attributes['id'])) {
            $result = QUI::getDataBase()->fetch(array(
                'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => array(
                    'id' => $attributes['id']
                )
            ));

            if (isset($result[0])) {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.id.already.exists'
                ));
            }

            $data['id'] = $attributes['id'];

        } else {
            // exist an id with 1000? field-id begin at 1000
            $result = QUI::getDataBase()->fetch(array(
                'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => array(
                    'id' => array(
                        'type' => '>=',
                        'value' => 1000
                    )
                ),
                'limit' => 1
            ));

            if (!isset($result[0])) {
                $data['id'] = 1000;
            }
        }


        // @todo create field permissions -> view und edit
        QUI::getPermissionManager()->addPermission(array());


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


        // add language var, if not exists
        self::setFieldTranslations($newId, $attributes);

        // create new cache column
        QUI::getDataBase()->table()->addColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            array('F' . $newId => 'text')
        );

        return self::getField($newId);
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
        $localeVar   = 'products.field.' . $fieldId . '.title';

        try {
            $data  = QUI\Translator::get($localeGroup, $localeVar);
            $texts = array();

            if (isset($attributes['titles'])) {
                $texts = $attributes['titles'];
            }

            $texts['datatype'] = 'php,js';
            $texts['html']     = 1;

            if (!isset($data[0])) {
                QUI\Translator::addUserVar($localeGroup, $localeVar, $texts);
            }

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addNotice($Exception->getMessage(), array(
                'trace' => $Exception->getTrace()
            ));
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
        return 'quiqqer/products/fields/field/' . $fieldId . '/';
    }

    /**
     * Return all available Fields
     *
     * @return array
     */
    public static function getFieldTypes()
    {
        $cacheName = 'quiqqer/products/fields';

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }

        // exists the type?
        $dir    = dirname(dirname(__FILE__)) . '/Field/Types/';
        $files  = QUI\Utils\System\File::readDir($dir);
        $result = array();

        foreach ($files as $file) {
            $file     = pathinfo($file);
            $result[] = $file['filename'];
        }

        QUI\Cache\Manager::set($cacheName, $result);

        return $result;
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
    public static function getFieldByType($type, $fieldId, $fieldParams = array())
    {
        $class = 'QUI\ERP\Products\Field\Types\\' . $type;

        if (class_exists($class)) {
            return new $class($fieldId, $fieldParams);
        }

        throw new QUI\Exception(array(
            'quiqqer/products',
            'exception.field.not.found',
            array(
                'fieldType' => $type,
                'fieldId' => $fieldId
            )
        ));
    }

    /**
     * Return a field
     *
     * @param integer $fieldId - Field-ID
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws QUI\Exception
     */
    public static function getField($fieldId)
    {
        if (isset(self::$list[$fieldId])) {
            return self::$list[$fieldId]; // @todo maybe with (clone) ??
        }

        try {
            $data = QUI\Cache\Manager::get(
                QUI\ERP\Products\Handler\Fields::getFieldCacheName($fieldId)
            );
        } catch (QUI\Exception $Exception) {
            $result = QUI::getDataBase()->fetch(array(
                'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => array(
                    'id' => (int)$fieldId
                ),
                'limit' => 1
            ));


            if (!isset($result[0])) {
                throw new QUI\Exception(
                    array('quiqqer/products', 'exception.field.not.found'),
                    404,
                    array('id' => (int)$fieldId)
                );
            }

            $data = $result[0];
        }


        // exists the type?
        $dir   = dirname(dirname(__FILE__)) . '/Field/Types/';
        $file  = $dir . $data['type'] . '.php';
        $class = 'QUI\ERP\Products\Field\Types\\' . $data['type'];

        if (!file_exists($file)) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.type.not.found'),
                404,
                array(
                    'id' => (int)$fieldId,
                    'type' => $data['type'],
                    'file' => $file
                )
            );
        }

        if (!class_exists($class)) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.class.not.found'),
                404,
                array(
                    'id' => (int)$fieldId,
                    'type' => $data['type'],
                    'class' => $class
                )
            );
        }

        $fieldData = array(
            'system' => (int)$data['systemField'],
            'required' => (int)$data['requiredField'],
            'standard' => (int)$data['standardField']
        );

        /* @var $Field QUI\ERP\Products\Field\Field */
        $Field = new $class($fieldId, $fieldData);

        if (!QUI\ERP\Products\Utils\Fields::isField($Field)) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.is.no.field'),
                404,
                array(
                    'id' => (int)$fieldId,
                    'type' => $data['type'],
                    'class' => $class
                )
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

        self::$list[$fieldId] = $Field;

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
    public static function getFieldIds($queryParams = array())
    {
        $query = array(
            'select' => 'id',
            'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName()
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

        if (!isset($queryParams['order'])) {
            $queryParams['order'] = 'priority ASC';
        }

        switch ($queryParams['order']) { // bad solution
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
                $query['order'] = 'priority ASC';
        }

        return QUI::getDataBase()->fetch($query);
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
     * @return array
     */
    public static function getFields($queryParams = array())
    {
        $result = array();
        $data   = self::getFieldIds($queryParams);

        foreach ($data as $entry) {
            try {
                $result[] = self::getField($entry['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException(
                    $Exception,
                    QUI\System\Log::LEVEL_NOTICE,
                    $Exception->getContext()
                );
            }
        }

        return $result;
    }

    /**
     * Return the number of the fields
     *
     * @param array $queryParams - query params (where, where_or)
     * @return integer
     */
    public static function countFields($queryParams = array())
    {
        $query = array(
            'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            'count' => array(
                'select' => 'id',
                'as' => 'count'
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
}
