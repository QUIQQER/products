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
            'priority'
        );
    }

    /**
     * @return array
     */
    public function getStandardFields()
    {
        // TODO: Implement getFrontendView() method.
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

        QUI::getDataBase()->insert(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data
        );

        $newId = QUI::getDataBase()->getPDO()->lastInsertId();


        return self::getField($newId);
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
     * @param integer $fieldId - Field-ID
     * @return QUI\ERP\Products\Field\Field
     *
     * @throws QUI\Exception
     */
    public static function getField($fieldId)
    {
        if (isset(self::$list[$fieldId])) {
            return self::$list[$fieldId];
        }

        $result = QUI::getDataBase()->fetch(array(
            'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            'where' => array(
                'id' => (int)$fieldId
            )
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.not.found'),
                404,
                array('id' => (int)$fieldId)
            );
        }

        $data = $result[0];

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

        /* @var $Field QUI\ERP\Products\Field\Field */
        $Field = new $class($fieldId);

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

        $Field->setAttributes($result[0]);

        if (empty($data['priority'])) {
            $data['priority'] = 0;
        }

        if (empty($data['prefix'])) {
            $data['prefix'] = '';
        }

        if (empty($data['suffix'])) {
            $data['suffix'] = '';
        }

        $Field->setAttribute('priority', $data['priority']);
        $Field->setAttribute('prefix', $data['prefix']);
        $Field->setAttribute('suffix', $data['suffix']);

        self::$list[$fieldId] = $Field;

        return $Field;
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
        $query = array(
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

        if (isset($queryParams['order'])) {
            $query['order'] = $queryParams['order'];
        }

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

        foreach ($data as $entry) {
            try {
                $result[] = self::getField($entry['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException(
                    $Exception,
                    QUI\System\Log::LEVEL_DEBUG,
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
