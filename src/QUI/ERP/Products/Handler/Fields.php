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
     * Create a new field
     *
     * @param array $attributes - field attributes
     * @return QUI\ERP\Products\Field\Field
     */
    public static function createField($attributes = array())
    {
        QUI\Rights\Permission::checkPermission('field.create');

        $data = array();

        if (isset($attributes['name'])) {
            $data['name'] = $attributes['name'];
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

        // exists the type?
        $dir   = dirname(dirname(__FILE__)) . 'Field/Types/';
        $file  = $dir . $result[0]['type'] . '.php';
        $class = 'QUI\ERP\Products\Field\Types\\' . $result[0]['type'];

        if (!file_exists($file)) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.type.not.found'),
                404,
                array(
                    'id' => (int)$fieldId,
                    'type' => $result[0]['type']
                )
            );
        }

        if (!class_exists($class)) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.class.not.found'),
                404,
                array(
                    'id' => (int)$fieldId,
                    'type' => $result[0]['type'],
                    'class' => $class
                )
            );
        }

        /* @var $Field QUI\ERP\Products\Interfaces\Field */
        $Field = new $class($fieldId);

        if (!QUI\ERP\Products\Utils\Fields::isField($Field)) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.field.is.no.field'),
                404,
                array(
                    'id' => (int)$fieldId,
                    'type' => $result[0]['type'],
                    'class' => $class
                )
            );
        }

        $Field->setName($result[0]['name']);


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
        $query['from'] = QUI\ERP\Products\Utils\Tables::getFieldTableName();

        $result = array();
        $data   = QUI::getDataBase()->fetch($query);

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

        foreach ($data as $entry) {
            try {
                $result[] = self::getField($entry['id']);
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }
}
