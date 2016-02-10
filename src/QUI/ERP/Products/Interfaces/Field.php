<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\Field
 */
namespace QUI\ERP\Products\Interfaces;

/**
 * Interface Field
 * @package QUI\ERP\Products\Interfaces
 */
interface Field
{
    /**
     * Return the field id
     *
     * @return integer
     */
    public function getId();

    /**
     * Return the field for the product as an array
     * @return array
     */
    public function toProductArray();

    /**
     * Set the field name
     *
     * @param mixed $value
     */
    public function setName($value);

    /**
     * Return the field name
     *
     * @return mixed
     */
    public function getName();

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * Set the field value
     *
     * @param mixed $value
     * @throws \QUI\Exception;
     */
    public function setValue($value);

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem();

    /**
     * Is the field a standard field?
     *
     * @return bool
     */
    public function isStandard();

    /**
     * Is the field a required field?
     *
     * @return boolean
     */
    public function isRequired();

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public static function validate($value);

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public static function cleanup($value);
}
