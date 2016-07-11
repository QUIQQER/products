<?php

/**
 * This file contains QUI\ERP\Products\Utils\User
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Interfaces\Users\User as UserInterface;

/**
 * Class User
 *
 * @package QUI\ERP\Products\Utils
 * @author www.pcsg.de (Henning Leutz)
 */
class User
{
    /**
     * netto flag
     */
    const IS_NETTO_USER = 1;

    /**
     * brutto flag
     */
    const IS_BRUTTO_USER = 2;

    /**
     * Return the brutto netto status
     * is the user a netto or brutto user
     *
     * @param UserInterface $User
     * @return bool
     */
    public static function getBruttoNettoUserStatus(UserInterface $User)
    {
        $nettoStatus = $User->getAttribute('quiqqer.erp.isNettoUser');

        if (is_numeric($nettoStatus)) {
            $nettoStatus = (int)$nettoStatus;
        }

        switch ($nettoStatus) {
            case self::IS_NETTO_USER:
            case self::IS_BRUTTO_USER:
                return $nettoStatus;
        }


        if ($User->getAttribute('quiqqer.erp.euVatId')
            || $User->getAttribute('quiqqer.erp.taxNumber')
        ) {
            return self::IS_NETTO_USER;
        }

        $Package = QUI::getPackage('quiqqer/tax');
        $Config  = $Package->getConfig();

        try {
            $Address = self::getUserERPAddress($User);

            if ($Address->getAttribute('company')) {
                if ($Config->getValue('shop', 'companyForceBruttoPrice')) {
                    return self::IS_BRUTTO_USER;
                }

                return self::IS_NETTO_USER;
            }
        } catch (QUI\Exception $Exception) {
            // no address found
        }

        $isNetto = $Config->getValue('shop', 'isNetto');

        if ($isNetto) {
            return self::IS_NETTO_USER;
        }


        try {
            $Tax = QUI\ERP\Tax\Utils::getTaxByUser($User);

            if ($Tax->getValue() == 0) {
                return self::IS_NETTO_USER;
            }

        } catch (QUI\Exception $Exception) {
            return self::IS_NETTO_USER;
        }

        return self::IS_BRUTTO_USER;
    }

    /**
     * Is the user a netto user?
     *
     * @param UserInterface $User
     * @return bool
     */
    public static function isNettoUser(UserInterface $User)
    {
        return self::getBruttoNettoUserStatus($User) === self::IS_NETTO_USER;
    }


    /**
     * Return the area of the user
     * if user is in no area, the default one of the shop would be used
     *
     * @param UserInterface $User
     * @return bool|QUI\ERP\Areas\Area
     */
    public static function getUserArea(UserInterface $User)
    {
        $Country = $User->getCountry();
        $Area    = QUI\ERP\Areas\Utils::getAreaByCountry($Country);

        if ($Area) {
            return $Area;
        }

        return self::getShopArea();
    }

    /**
     * Return the user ERP address (Rechnungsaddresse, Accounting Address)
     *
     * @param UserInterface $User
     * @return false|QUI\Users\Address
     * @throws QUI\Exception
     */
    public static function getUserERPAddress(UserInterface $User)
    {
        if (!QUI::getUsers()->isUser($User)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.no.user'
            ));
        }

        /* @var $User QUI\Users\User */
        if (!$User->getAttribute('quiqqer.erp.address')) {
            return $User->getStandardAddress();
        }

        $erpAddress = $User->getAttribute('quiqqer.erp.address');

        try {
            return $User->getAddress($erpAddress);
        } catch (QUI\Exception $Exception) {
        }

        return $User->getStandardAddress();
    }

    /**
     * Return the area of the shop
     *
     * @return int
     * @throws QUI\Exception
     */
    public static function getShopArea()
    {
        $Areas        = new QUI\ERP\Areas\Handler();
        $Package      = QUI::getPackage('quiqqer/tax');
        $Config       = $Package->getConfig();
        $standardArea = $Config->getValue('shop', 'area');

        return $Areas->getChild($standardArea);
    }
}
