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



    public function getBackendView();



    public function getFrontendView();

    /**
     * Set the field value
     *
     * @param mixed $value
     * @throws \QUI\Exception;
     */
    public function setValue($value);

    /**
     * Check the field value
     * is the value valid?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function checkValue($value);
}
