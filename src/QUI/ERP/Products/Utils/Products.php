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
     * Is mixed a product compatible object?
     * looks for:
     * - QUI\ERP\Products\Interfaces\ProductInterface::class
     * - QUI\ERP\Products\Product\Model
     * - QUI\ERP\Products\Product\Product
     *
     * @param $mixed
     * @return bool
     */
    public static function isProduct($mixed)
    {
        if (\get_class($mixed) == QUI\ERP\Products\Product\Model::class) {
            return true;
        }

        if (\get_class($mixed) == QUI\ERP\Products\Product\Product::class) {
            return true;
        }

        if ($mixed instanceof QUI\ERP\Products\Interfaces\ProductInterface) {
            return true;
        }

        return false;
    }

    /**
     * Return the price field from the product for the user
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface|QUI\ERP\Products\Product\Model $Product
     * @param QUI\Interfaces\Users\User|null $User
     * @return QUI\ERP\Money\Price
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
        $priceList = \array_merge(
            $Product->getFieldsByType(FieldHandler::TYPE_PRICE),
            $Product->getFieldsByType(FieldHandler::TYPE_PRICE_BY_QUANTITY)
        );

        if (empty($priceList)) {
            return new QUI\ERP\Money\Price($PriceField->getValue(), $Currency);
        }

        $priceFields = \array_filter($priceList, function ($Field) use ($User) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */

            // ignore default main price
            if ($Field->getId() == FieldHandler::FIELD_PRICE) {
                return false;
            };

            $options = $Field->getOptions();

            if (!isset($options['groups'])) {
                return true;
            }

            if (isset($options['ignoreForPriceCalculation'])
                && $options['ignoreForPriceCalculation'] == 1
            ) {
                return false;
            }

            $groups = \explode(',', $options['groups']);

            if (empty($groups)) {
                return true;
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
            $type = 'QUI\\ERP\\Products\\Field\\Types\\'.$Field->getType();

            if (\is_callable([$type, 'onGetPriceFieldForProduct'])) {
                try {
                    $ParentField = FieldHandler::getField($Field->getId());
                    $value       = $ParentField->onGetPriceFieldForProduct($Product, $User);

                    if ($value && $value < $PriceField->getValue()) {
                        $priceValue = $value;
                    }
                } catch (QUI\Exception $Exception) {
                }

                continue;
            }

            $value = $Field->getValue();

            if ($value === false || $value === '' || $value === null) {
                continue;
            }

            if ($value < $PriceField->getValue()) {
                $priceValue = $value;
            }
        }

        return new QUI\ERP\Money\Price($priceValue, $Currency);
    }

    /**
     * Return generate variant hash
     *
     * @param array $fields - could be a field array [Field, Field, Field],
     *                        or could be a field object list ['field-1':2, 'field-1':'value']
     * @return string
     */
    public static function generateVariantHashFromFields($fields)
    {
        $hash = [];

        // get hash values
        foreach ($fields as $Field => $fieldValue) {
            if ($fieldValue instanceof QUI\ERP\Products\Interfaces\FieldInterface) {
                $fieldId    = $fieldValue->getId();
                $fieldValue = $fieldValue->getValue();
            } elseif (\is_string($Field) || \is_numeric($Field)) {
                $fieldId = $Field;
            } else {
                continue;
            }

            // string to hex
            if (!\is_numeric($fieldValue)) {
                $fieldValue = \implode(\unpack("H*", $fieldValue));
            }

            $hash[] = $fieldId.':'.$fieldValue;
        }

        // sort fields
        \usort($hash, function ($a, $b) {
            $aId = (int)\explode(':', $a)[0];
            $bId = (int)\explode(':', $b)[0];

            return $aId - $bId;
        });

        // generate hash
        $generate = ';'.\implode(';', $hash).';';

        return $generate;
    }

    /**
     * @param QUI\ERP\Products\Product\Product $Product
     */
    public static function setAvailableFieldOptions(QUI\ERP\Products\Product\Product $Product)
    {
        if (!($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) &&
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantChild)) {
            return;
        }

        $available = $Product->availableActiveChildFields();

        // attribute groups
        $groupList = $Product->getFieldsByType(
            QUI\ERP\Products\Handler\Fields::TYPE_ATTRIBUTE_GROUPS
        );

        foreach ($groupList as $Field) {
            /* @var $Field QUI\ERP\Products\Field\Types\AttributeGroup */
            $fieldId = $Field->getId();
            $Field->disableEntries();

            if (!isset($available[$fieldId])) {
                continue;
            }

            $options = $Field->getOptions();
            $entries = $options['entries'];

            $allowed = $available[$fieldId];
            $allowed = \array_flip($allowed);

            foreach ($entries as $key => $value) {
                $valueId = $value['valueId'];

                if (isset($allowed[$valueId])) {
                    $Field->enableEntry($key);
                }
            }
        }

        // attribute list
//        $attributeList = $Product->getFieldsByType(
//            QUI\ERP\Products\Handler\Fields::TYPE_ATTRIBUTE_LIST
//        );
//
//        foreach ($attributeList as $Field) {
//            /* @var $Field QUI\ERP\Products\Field\Types\AttributeGroup */
//            $fieldId = $Field->getId();
//            $Field->disableEntries();
//
//            if (!isset($available[$fieldId])) {
//                continue;
//            }
//
//            $options = $Field->getOptions();
//            $entries = $options['entries'];
//
//            $allowed = $available[$fieldId];
//            $allowed = \array_flip($allowed);
//
//            foreach ($entries as $key => $value) {
//                if (isset($allowed[$key])) {
//                    $Field->enableEntry($key);
//                }
//            }
//        }
    }
}
