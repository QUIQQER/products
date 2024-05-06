<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\FieldInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\Exception;

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
    public function toProductArray(): array;

    /**
     * Is the field empty?
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Set the field name
     *
     * @param mixed $value
     */
    public function setName(mixed $value): void;

    /**
     * Set the field value
     *
     * @param mixed $value
     * @throws Exception;
     */
    public function setValue(mixed $value): void;

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws Exception
     */
    public function validate(mixed $value): void;

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup(mixed $value): mixed;

    /**
     * Deleted
     */

    /**
     * Set the unassigned field status
     *
     * @param boolean $status
     */
    public function setUnassignedStatus(bool $status): void;
}
