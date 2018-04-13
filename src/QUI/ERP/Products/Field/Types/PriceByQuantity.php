<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Price
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class PriceByQuantity
 *
 * @package QUI\ERP\Products\Field
 */
class PriceByQuantity extends Price
{
    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * Return the price value
     *
     * @param QUI\ERP\Products\Product\UniqueProduct $Product
     * @param null $User
     * @return integer|double|float|bool
     */
    public function onGetPriceFieldForProduct($Product, $User = null)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        return $this->getValueDependendByProduct($Product);
    }

    /**
     * Return the price value
     *
     * @param QUI\ERP\Products\Product\UniqueProduct $Product - optional
     * @return integer|double|float|bool
     */
    public function getValueDependendByProduct($Product)
    {
        if (!QUI\ERP\Products\Utils\Products::isProduct($Product)) {
            return false;
        }

        if (!($Product instanceof QUI\ERP\Products\Product\UniqueProduct)) {
            return false;
        }

        if (!$Product->getQuantity()) {
            return false;
        }

        $RealProduct = new QUI\ERP\Products\Product\Product($Product->getId());
        $value       = $RealProduct->getFieldValue($this->getId());

        if (empty($value)) {
            return false;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return false;
        }

        if ((int)$value['quantity'] > $Product->getQuantity()) {
            return false;
        }

        return $value['price'];
    }

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getAttributes());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        $value = $this->cleanup($this->getValue());

        $Price = new QUI\ERP\Money\Price(
            $value['price'],
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        $valueText = QUI::getLocale()->get('quiqqer/products', 'fieldtype.PriceByQuantity.frontend.text', [
            'price'    => $Price->getDisplayPrice(),
            'quantity' => (int)$value['quantity']
        ]);

        return new View([
            'id'       => $this->getId(),
            'value'    => $valueText,
            'title'    => $this->getTitle(),
            'prefix'   => $this->getAttribute('prefix'),
            'suffix'   => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ]);
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByQuantity';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByQuantitySettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @return array
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        return $this->cleanup($value);
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * Precision: 8 (important for currencies like BitCoin)
     *
     * @param string|array $value
     * @return array
     */
    public function cleanup($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $defaultReturn = [
            'price'    => '',
            'quantity' => '',
        ];

        if (!isset($value['price']) || !isset($value['quantity'])) {
            return $defaultReturn;
        }

        $price    = $value['price'];
        $quantity = $value['quantity'];

        if (is_float($price)) {
            return [
                'price'    => $price,
                'quantity' => (int)$quantity
            ];
        }

        $localeCode = QUI::getLocale()->getLocalesByLang(
            QUI::getLocale()->getCurrent()
        );

        $Formatter = new \NumberFormatter($localeCode[0], \NumberFormatter::DECIMAL);
        $price     = $Formatter->parse($price);

        return [
            'price'    => round(floatval($price), 8),
            'quantity' => (int)$quantity,
        ];
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (is_string($this->value)) {
            $value = json_decode($this->value, true);
        } else {
            $value = $this->value;
        }

        if (!isset($value['price']) || !isset($value['quantity'])) {
            return true;
        }

        if (!$value['price']) {
            return true;
        }

        return false;
    }
}
