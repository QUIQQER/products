<?php

/**
 * This file contains QUI\ERP\Products\Utils\Products
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class Products Helper
 *
 * @package QUI\ERP\Products\Utils
 */
class Products
{
    /**
     * Is mixed a product compatible object?
     * looks for:
     * - QUI\ERP\Products\Interfaces\Product::class
     * - QUI\ERP\Products\Product\Model
     * - QUI\ERP\Products\Product\Product
     *
     * @param $mixed
     * @return bool
     */
    public static function isProduct($mixed)
    {
        if (get_class($mixed) == QUI\ERP\Products\Product\Model::class) {
            return true;
        }

        if (get_class($mixed) == QUI\ERP\Products\Product\Product::class) {
            return true;
        }

        if ($mixed instanceof QUI\ERP\Products\Interfaces\Product) {
            return true;
        }

        return false;
    }

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

        if (!self::isProduct($Product)) {
            throw new QUI\Exception('No Product given');
        }

        $PriceField = $Product->getField(FieldHandler::FIELD_PRICE);
        $priceValue = $PriceField->getValue();
        $Currency   = QUI\ERP\Currency\Handler::getDefaultCurrency();

        // exists more price fields?
        // is user in group filter
        $priceList = array_merge(
            $Product->getFieldsByType('Price'),
            $Product->getFieldsByType('PriceByQuantity')
        );

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
            $type = 'QUI\\ERP\\Products\\Field\\Types\\' . $Field->getType();

            if (is_callable(array($type, 'onGetPriceFieldForProduct'))) {
                try {
                    $ParentField = Fields::getField($Field->getId());
                    $value       = $ParentField->onGetPriceFieldForProduct($Product, $User);

                    if ($value && $value < $PriceField->getValue()) {
                        $PriceField = $Field;
                        $priceValue = $value;
                    }
                } catch (QUI\Exception $Exception) {
                }

                continue;
            }

            if ($Field->getValue() === false) {
                continue;
            }

            if ($Field->getValue() < $PriceField->getValue()) {
                $PriceField = $Field;
                $priceValue = $Field->getValue();
            }
        }

        return new QUI\ERP\Products\Utils\Price($priceValue, $Currency);
    }
}
