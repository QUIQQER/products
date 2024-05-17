<?php

/**
 * This file contains QUI\ERP\Products\Utils\User
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Utils\User as Utils;
use QUI\Interfaces\Users\User as UserInterface;

/**
 * Class User
 *
 * @package QUI\ERP\Products\Utils
 * @author www.pcsg.de (Henning Leutz)
 * @deprecated
 */
class User
{
    /**
     * netto flag
     * @deprecated
     */
    const IS_NETTO_USER = Utils::IS_NETTO_USER;

    /**
     * brutto flag
     * @deprecated
     */
    const IS_BRUTTO_USER = Utils::IS_BRUTTO_USER;

    /**
     * Return the brutto netto status
     * is the user a netto or brutto user
     *
     * @param UserInterface $User
     * @return int
     * @deprecated
     */
    public static function getBruttoNettoUserStatus(UserInterface $User): int
    {
        return Utils::getBruttoNettoUserStatus($User);
    }

    /**
     * Is the user a netto user?
     *
     * @param UserInterface $User
     * @return bool
     * @deprecated
     */
    public static function isNettoUser(UserInterface $User): bool
    {
        return Utils::isNettoUser($User);
    }

    /**
     * Return the area of the user
     * if user is in no area, the default one of the shop would be used
     *
     * @param UserInterface $User
     * @return bool|QUI\ERP\Areas\Area
     * @deprecated
     */
    public static function getUserArea(UserInterface $User): bool|QUI\ERP\Areas\Area
    {
        return Utils::getUserArea($User);
    }

    /**
     * Return the user ERP address (Rechnungsaddresse, Accounting Address)
     *
     * @param UserInterface $User
     * @return false|QUI\Users\Address
     * @throws QUI\Exception
     * @deprecated
     */
    public static function getUserERPAddress(UserInterface $User): bool|QUI\Users\Address
    {
        return Utils::getUserERPAddress($User);
    }

    /**
     * Return the area of the shop
     *
     * @return QUI\ERP\Areas\Area
     * @throws QUI\Exception
     * @deprecated use QUI\ERP\Defaults::getShopArea()
     */
    public static function getShopArea(): QUI\ERP\Areas\Area
    {
        return QUI\ERP\Defaults::getArea();
    }
}
