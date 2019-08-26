<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class PriceByTimePeriod
 *
 * Price that is valid in a given time period
 */
class PriceByTimePeriod extends Price
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
     * @throws QUI\Exception
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
     * @throws QUI\Exception
     */
    public function getValueDependendByProduct($Product)
    {
        if (!QUI\ERP\Products\Utils\Products::isProduct($Product)) {
            return false;
        }

        if (!($Product instanceof QUI\ERP\Products\Product\UniqueProduct)) {
            return false;
        }

        $RealProduct = QUI\ERP\Products\Handler\Products::getNewProductInstance($Product->getId());
        $value       = $RealProduct->getFieldValue($this->getId());

        if (empty($value)) {
            return false;
        }

        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        if (!\is_array($value)) {
            return false;
        }

        $From = \date_create($value['from']);
        $To   = \date_create($value['to']);
        $Now  = \date_create();

        if ($From !== false && $From > $Now) {
            return false;
        }

        if ($To !== false && $To < $Now) {
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

        $valueText = $Price->getDisplayPrice();

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
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriodSettings';
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
        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        $defaultReturn = [
            'price' => '',
            'from'  => false,
            'to'    => false
        ];

        if (!isset($value['price']) || !isset($value['from']) || !isset($value['to'])) {
            return $defaultReturn;
        }

        $price = $value['price'];
        $From  = \date_create($value['from']);
        $To    = \date_create($value['to']);

        if (!\is_float($price)) {
            $localeCode = QUI::getLocale()->getLocalesByLang(
                QUI::getLocale()->getCurrent()
            );

            $Formatter = new \NumberFormatter($localeCode[0], \NumberFormatter::DECIMAL);
            $price     = $Formatter->parse($price);
        }

        return [
            'price' => $price,
            'from'  => $From ? $From->format('Y-m-d') : false,
            'to'    => $To ? $To->format('Y-m-d') : false
        ];
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (\is_string($this->value)) {
            $value = \json_decode($this->value, true);
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
