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
     * Return the value in dependence of a locale (language)
     *
     * @param \QUI\Locale|boolean $Locale - optional
     * @return mixed
     */
    public function getValueByLocale($Locale = false);

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

    /**
     * Is the field an own field
     *
     * @return boolean
     */
    public function isOwnField();

    /**
     * Is the field public
     * is the field visible by visitors
     *
     * @return boolean
     */
    public function isPublic();
}
