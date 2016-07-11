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

        if (is_bool($nettoStatus)) {
            $nettoStatus = 0;
        }

        $nettoStatus = (int)$nettoStatus;

        switch ($nettoStatus) {
            case self::IS_NETTO_USER:
                return true;
                break;

            case self::IS_BRUTTO_USER:
                return false;
                break;
        }


        $Taxes = new QUI\ERP\Tax\Handler();
        $Areas = new QUI\ERP\Areas\Handler();

        $Package = QUI::getPackage('quiqqer/tax');
        $Config  = $Package->getConfig();

        $standardTax  = $Config->getValue('shop', 'tax');
        $standardArea = $Config->getValue('shop', 'area');
        $isNetto      = $Config->getValue('shop', 'isNetto');

        try {
            $TaxGroup = $Taxes->getTaxGroup($standardTax);
            $Area     = $Areas->getChild($standardArea);

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_CRITICAL);
            return self::IS_NETTO_USER;
        }


        // @todo status setzen
        // @todo muss kontrolliert werden


        return self::IS_NETTO_USER;
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