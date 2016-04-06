<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\UniqueField
 */
namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Field\View;

/**
 * Interface UniqueField
 *
 * @package QUI\ERP\Products\Interfaces
 */
interface UniqueField
{
    /**
     * Return the field id
     *
     * @return integer
     */
    public function getId();

    /**
     * Return the field name
     *
     * @return mixed
     */
    public function getName();

    /**
     * Return the title / name of the field
     *
     * @param \QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false);

    /**
     * Return the current value
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Return the view
     *
     * @return View
     */
    public function getView();

    /**
     * Return the feld as array
     * return all attributes of the field
     *
     * @return array
     */
    public function getAttributes();

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
     * Is the field unassigned
     *
     * @return boolean
     */
    public function isUnassigned();
}
