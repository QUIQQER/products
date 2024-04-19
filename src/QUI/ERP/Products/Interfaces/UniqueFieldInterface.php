<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\UniqueFieldInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\Locale;
use QUI\ERP\Products\Field\View;

/**
 * Interface UniqueField
 *
 * @package QUI\ERP\Products\Interfaces
 */
interface UniqueFieldInterface
{
    /**
     * Return the field id
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Return the field name
     *
     * @return mixed
     */
    public function getName(): mixed;

    /**
     * Return the title / name of the field
     *
     * @param Locale|null $Locale - optional
     * @return string
     */
    public function getTitle(Locale $Locale = null): string;

    /**
     * Return the current value
     *
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * Return the value in dependence of a locale (language)
     *
     * @param Locale|null $Locale $Locale - optional
     * @return mixed
     */
    public function getValueByLocale(Locale $Locale = null): mixed;

    /**
     * Return value for use in product search cache
     *
     * @param Locale|null $Locale
     * @return string|array|null
     */
    public function getSearchCacheValue(Locale $Locale = null): null|string|array;

    /**
     * Return the view
     *
     * @return View
     */
    public function getView(): View;

    /**
     * Return the feld as array
     * return all attributes of the field
     *
     * @return array
     */
    public function getAttributes(): array;

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem(): bool;

    /**
     * Is the field a standard field?
     *
     * @return bool
     */
    public function isStandard(): bool;

    /**
     * Is the field a required field?
     *
     * @return boolean
     */
    public function isRequired(): bool;

    /**
     * Is the field unassigned
     *
     * @return boolean
     */
    public function isUnassigned(): bool;

    /**
     * Is the field an own field
     *
     * @return boolean
     */
    public function isOwnField(): bool;

    /**
     * Is the field public
     * is the field visible by visitors
     *
     * @return boolean
     */
    public function isPublic(): bool;

    /**
     * Should the field be displayed in the details?
     *
     * @return boolean
     */
    public function showInDetails(): bool;
}
