<?php

/**
 * This file contains QUI\ERP\Products\Utils\Products
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields as FieldHandler;

/**
 * Class Products Helper
 *
 * @package QUI\ERP\Products\Utils
 */
class Products
{
    /**
     * Return the price field from the product for the user
     *
     * @param QUI\ERP\Products\Interfaces\Product|QUI\ERP\Products\Product\Model $Product
     * @param QUI\Interfaces\Users\User|null $User
     * @return QUI\ERP\Products\Utils\Price
     *
     * @throws QUI\Exception
     */
    public static function getPriceFieldForProduct($Product, $User = null)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        if (get_class($Product) != QUI\ERP\Products\Interfaces\Product::class &&
            !($Product instanceof QUI\ERP\Products\Interfaces\Product)
        ) {
            throw new QUI\Exception('No Product given');
        }

        $PriceField = $Product->getField(FieldHandler::FIELD_PRICE);
        $Currency   = QUI\ERP\Currency\Handler::getDefaultCurrency();

        // exists more price fields?
        // is user in group filter
        $priceList = $Product->getFieldsByType('Price');

        if (empty($priceList)) {
            return new QUI\ERP\Products\Utils\Price($PriceField->getValue(), $Currency);
        }

        $priceFields = array_filter($priceList, function ($Field) use ($User) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */

            // ignore default main price
            if ($Field->getId() == FieldHandler::FIELD_PRICE) {
                return false;
            };

            $options = $Field->getOptions();

            if (!isset($options['groups'])) {
                return false;
            }

            if (isset($options['ignoreForPriceCalculation'])
                && $options['ignoreForPriceCalculation'] == 1
            ) {
                return false;
            }

            $groups = explode(',', $options['groups']);

            if (empty($groups)) {
                return false;
            }

            foreach ($groups as $gid) {
                if ($User->isInGroup($gid)) {
                    return true;
                }
            }

            return false;
        });

        // use the lowest price?
        foreach ($priceFields as $Field) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */
            if ($Field->getValue() < $PriceField->getValue()) {
                $PriceField = $Field;
            }
        }

        return new QUI\ERP\Products\Utils\Price($PriceField->getValue(), $Currency);
    }
}
