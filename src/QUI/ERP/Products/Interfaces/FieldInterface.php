<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\FieldInterface
 */

namespace QUI\ERP\Products\Interfaces;

/**
 * Interface Field
 * @package QUI\ERP\Products\Interfaces
 */
interface FieldInterface extends UniqueFieldInterface
{
    /**
     * Return the field for the product as an array
     *
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
     * Set the field value
     *
     * @param mixed $value
     * @throws \QUI\Exception;
     */
    public function setValue($value);

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value);

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup($value);

    /**
     * Deleted
     */

    /**
     * Set the unassigned field status
     *
     * @param boolean $status
     */
    public function setUnassignedStatus($status);
}
