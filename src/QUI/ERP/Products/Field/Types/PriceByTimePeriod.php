<?php

namespace QUI\ERP\Products\Field\Types;

use NumberFormatter;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;

use function date_create;
use function is_array;
use function is_float;
use function is_string;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

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
     * @param QUI\ERP\Products\Interfaces\ProductInterface $Product
     * @param null $User
     * @return integer|float|bool
     * @throws QUI\Exception
     */
    public function onGetPriceFieldForProduct(
        QUI\ERP\Products\Interfaces\ProductInterface $Product,
        $User = null
    ): float|bool|int {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        return $this->getValueDependendByProduct($Product);
    }

    /**
     * Return the price value
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface $Product - optional
     * @return integer|float|bool
     * @throws QUI\Exception
     */
    public function getValueDependendByProduct(QUI\ERP\Products\Interfaces\ProductInterface $Product): float|bool|int
    {
        if (!QUI\ERP\Products\Utils\Products::isProduct($Product)) {
            return false;
        }

        if ($Product instanceof QUI\ERP\Products\Product\UniqueProduct) {
            $RealProduct = QUI\ERP\Products\Handler\Products::getNewProductInstance($Product->getId());
        } else {
            $RealProduct = $Product;
        }

        $value = $RealProduct->getFieldValue($this->getId());

        if (empty($value)) {
            return false;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return false;
        }

        $From = false;
        $To = false;

        if (!empty($value['from'])) {
            $From = date_create($value['from']);
        }

        if (!empty($value['to'])) {
            $To = date_create($value['to']);
        }

        $Now = date_create();
        $Now->setTime(0, 0);

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
    public function getBackendView(): View
    {
        return new View($this->getAttributes());
    }

    /**
     * @return View
     * @throws QUI\Exception
     */
    public function getFrontendView(): View
    {
        $value = $this->cleanup($this->getValue());

        $Price = new QUI\ERP\Money\Price(
            $value['price'],
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        $valueText = $Price->getDisplayPrice();

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
            'from' => false,
            'to' => false
        ];

        if (!isset($value['price']) || !isset($value['from']) || !isset($value['to'])) {
            return $defaultReturn;
        }

        $price = $value['price'];
        $From = false;
        $To = false;

        if (!empty($value['from'])) {
            $From = date_create($value['from']);
        }

        if (!empty($value['to'])) {
            $To = date_create($value['to']);
        }

        if (!is_float($price)) {
            $localeCode = QUI::getLocale()->getLocalesByLang(
                QUI::getLocale()->getCurrent()
            );

            $Formatter = new NumberFormatter($localeCode[0], NumberFormatter::DECIMAL);
            $price = $Formatter->parse($price);
        }

        return [
            'price' => $price,
            'from' => $From ? $From->format('Y-m-d H:i') : false,
            'to' => $To ? $To->format('Y-m-d H:i') : false
        ];
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriodSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws Exception
     */
    public function validate($value): void
    {
        if (empty($value)) {
            return;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception([
                    'quiqqer/products',
                    'exception.field.invalid',
                    [
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle(),
                        'fieldType' => $this->getType()
                    ]
                ]);
            }
        }

        if (!is_array($value)) {
            throw new Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                ]
            ]);
        }

        if (!isset($value['price']) || !isset($value['from']) || !isset($value['to'])) {
            throw new Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                ]
            ]);
        }
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

        if (!isset($value['price'])) {
            return true;
        }

        if (!$value['price']) {
            return true;
        }

        return false;
    }
}
