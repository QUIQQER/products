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

        $Price = new QUI\ERP\Products\Utils\Price(
            $value['price'],
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        return new View(array(
            'id'       => $this->getId(),
            'value'    => $Price->getDisplayPrice(),
            'title'    => $this->getTitle(),
            'prefix'   => $this->getAttribute('prefix'),
            'suffix'   => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
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

        $defaultReturn = array(
            'price'    => '',
            'quantity' => '',
        );

        if (!isset($value['price']) || !isset($value['quantity'])) {
            return $defaultReturn;
        }

        $price    = $value['price'];
        $quantity = $value['quantity'];

        $decimalSeperator   = QUI::getLocale()->getDecimalSeperator();//'.';
        $thousandsSeperator = QUI::getLocale()->getGroupingSeperator();//',';

        if (is_float($price)) {
            return array(
                'price'    => $price,
                'quantity' => (int)$quantity
            );
        }

        $price = (string)$price;
        $price = preg_replace('#[^\d,.]#i', '', $price);

        if (trim($price) === '') {
            return $defaultReturn;
        }

        $decimal   = mb_strpos($price, $decimalSeperator);
        $thousands = mb_strpos($price, $thousandsSeperator);

        if ($thousands === false && $decimal === false) {
            return array(
                'price'    => round(floatval($price), 8),
                'quantity' => (int)$quantity,
            );
        }

        if ($thousands !== false && $decimal === false) {
            if (mb_substr($price, -8, 1) === $decimalSeperator) {
                $price = str_replace($thousandsSeperator, '', $price);
            }
        }

        if ($thousands === false && $decimal !== false) {
            $price = str_replace(
                $decimalSeperator,
                '.',
                $price
            );
        }

        if ($thousands !== false && $decimal !== false) {
            $price = str_replace($decimalSeperator, '', $price);
            $price = str_replace($thousandsSeperator, '.', $price);
        }

        return array(
            'price'    => round(floatval($price), 8),
            'quantity' => (int)$quantity,
        );
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