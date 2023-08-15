<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Price
 */

namespace QUI\ERP\Products\Field\Types;

use NumberFormatter;
use QUI;
use QUI\ERP\Products\Field\View;

use function is_float;
use function is_string;
use function json_decode;
use function round;

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

        $RealProduct = QUI\ERP\Products\Handler\Products::getNewProductInstance($Product->getId());
        $value = $RealProduct->getFieldValue($this->getId());

        if (empty($value)) {
            return false;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!\is_array($value)) {
            return false;
        }

        if (empty($value['price'])) {
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
     * @throws \QUI\Exception
     */
    public function getFrontendView()
    {
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());
        $value = $this->cleanup($this->getValue());

        $Price = new QUI\ERP\Money\Price(
            $Calc->getPrice($value['price']),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        $valueText = QUI::getLocale()->get('quiqqer/products', 'fieldtype.PriceByQuantity.frontend.text', [
            'price' => $Price->getDisplayPrice(),
            'quantity' => (int)$value['quantity']
        ]);

        return new View([
            'id' => $this->getId(),
            'value' => $valueText,
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ]);
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * Precision: 8 (important for currencies like BitCoin)
     *
     * @param string|array $value
     * @return array
     */
    public function cleanup($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $defaultReturn = [
            'price' => '',
            'quantity' => '',
        ];

        if (!isset($value['price']) || !isset($value['quantity'])) {
            return $defaultReturn;
        }

        $price = $value['price'];
        $quantity = $value['quantity'];

        if (empty($quantity) || empty($price)) {
            return $defaultReturn;
        }

        if (is_float($price)) {
            return [
                'price' => $price,
                'quantity' => (int)$quantity
            ];
        }

        $localeCode = QUI::getLocale()->getLocalesByLang(
            QUI::getLocale()->getCurrent()
        );

        $Formatter = new NumberFormatter($localeCode[0], NumberFormatter::DECIMAL);
        $price = $Formatter->parse($price);

        return [
            'price' => round(floatval($price), QUI\ERP\Defaults::getPrecision()),
            'quantity' => (int)$quantity,
        ];
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByQuantity';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
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
    public function validate($value): array
    {
        return $this->cleanup($value);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
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
