<?php

/**
 * This file contains QUI\ERP\Products\Product\TextProduct
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Interfaces\UniqueFieldInterface;
use QUI\Exception;
use QUI\Locale;
use QUI\Projects\Media\Image;

use function array_map;
use function array_merge;

/**
 * Class TextProduct
 *
 * @package QUI\ERP\Products\Product
 */
class TextProduct extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * TextProduct constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'displayPrice' => false
        ]);

        $attributes['vat'] = 0;
        $attributes['calculated']['vatArray'] = [];

        $this->setAttributes($attributes);
    }

    /**
     * @param null $User
     * @return UniqueProduct
     *
     * @throws Exception
     * @throws Exception
     * @throws QUI\ExceptionStack
     * @throws QUI\Users\Exception
     */
    public function createUniqueProduct($User = null): UniqueProduct
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        $attributes = $this->getAttributes();
        $attributes['title'] = $this->getTitle();
        $attributes['description'] = $this->getDescription();
        $attributes['uid'] = $User->getUUID();
        $attributes['displayPrice'] = false;
        $attributes['maximumQuantity'] = $this->getMaximumQuantity();

        $attributes['fields'] = array_map(function ($Field) {
            /* @var $Field QUI\ERP\Products\Field\Field */
            return array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );
        }, $this->getFields());

        QUI::getEvents()->fireEvent(
            'quiqqerProductsToUniqueProduct',
            [$this, &$attributes]
        );

        $attributes['calculated']['vatArray'] = [];

        return new UniqueProduct($this->getId(), $attributes);
    }

    /**
     * Return the Product-ID
     *
     * @return int
     */
    public function getId(): int
    {
        return -1;
    }

    /**
     * Return the translated title
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getTitle(QUI\Locale $Locale = null): string
    {
        if (!$this->existsAttribute('title')) {
            return '';
        }

        return $this->getAttribute('title');
    }

    /**
     * Return the translated description
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(QUI\Locale $Locale = null): string
    {
        if (!$this->existsAttribute('description')) {
            return '';
        }

        return $this->getAttribute('description');
    }

    /**
     * Return the translated content
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getContent(QUI\Locale $Locale = null): string
    {
        return '';
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = [];

        $addField = function ($fieldId) use (&$fields) {
            try {
                $fields[] = $this->getField($fieldId);
            } catch (Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage(), $Exception->getContext());
            }
        };

        $addField(Fields::FIELD_PRICE);
        $addField(Fields::FIELD_VAT);
        $addField(Fields::FIELD_TITLE);
        $addField(Fields::FIELD_SHORT_DESC);
        $addField(Fields::FIELD_CONTENT);

        return QUI\ERP\Products\Utils\Fields::sortFields($fields);
    }

    /**
     * Return the field
     *
     * @param integer $fieldId
     * @return FieldInterface|null
     * @throws Exception
     */
    public function getField(int $fieldId): ?QUI\ERP\Products\Interfaces\FieldInterface
    {
        switch ($fieldId) {
            case Fields::FIELD_PRICE:
                return new QUI\ERP\Products\Field\Types\Price($fieldId, [
                    'value' => $this->getPrice()
                ]);

            case Fields::FIELD_VAT:
                return new QUI\ERP\Products\Field\Types\Vat($fieldId, [
                    'value' => $this->getAttribute('vat'),
                    'defaultValue' => $this->getAttribute('vat')
                ]);

            case Fields::FIELD_CONTENT:
                return new QUI\ERP\Products\Field\Types\Textarea($fieldId, [
                    'value' => ''
                ]);
        }


        $Field = new QUI\ERP\Products\Field\Types\Input($fieldId);

        switch ($fieldId) {
            case Fields::FIELD_PRODUCT_NO:
                $Field->setDefaultValue($this->getAttribute('articleNo'));
                $Field->setValue($this->getAttribute('articleNo'));
                break;

            case Fields::FIELD_TITLE:
                $Field->setDefaultValue($this->getTitle());
                $Field->setValue($this->getTitle());
                break;
        }

        return $Field;
    }

    /**
     * Return the field attribute / value of the product
     *
     * @param integer $fieldId
     * @return mixed
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getFieldValue(int $fieldId): mixed
    {
        throw new QUI\ERP\Products\Product\Exception([
            'quiqqer/products',
            'exception.field.id_in_product_not_found',
            [
                'fieldId' => $fieldId,
                'productId' => $this->getId()
            ]
        ], 1002);
    }

    /**
     * Return all fields from the wanted type
     *
     * @param string|array $type
     * @return array
     */
    public function getFieldsByType(string|array $type): array
    {
        return [];
    }

    /**
     * Return the main product image
     *
     * @return Image
     * @throws Exception
     */
    public function getImage(): Image
    {
        $Placeholder = QUI::getRewrite()->getProject()->getMedia()->getPlaceholderImage();

        if ($Placeholder instanceof Image) {
            return $Placeholder;
        }

        throw new QUI\ERP\Products\Product\Exception([
            'quiqqer/products',
            'exception.product.no.image',
            [
                'productId' => $this->getId()
            ]
        ]);
    }

    /**
     * Return the price object of the product
     *
     * @return QUI\ERP\Money\Price
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * @return QUI\ERP\Money\Price
     */
    public function getOriginalPrice(): QUI\ERP\Money\Price
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * @return false|QUI\ERP\Money\Price|QUI\ERP\Products\Interfaces\UniqueFieldInterface
     */
    public function getOfferPrice(): UniqueFieldInterface|bool|QUI\ERP\Money\Price
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * Return the lowest possible price
     *
     * @return QUI\ERP\Money\Price
     */
    public function getMinimumPrice(): QUI\ERP\Money\Price
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * Return the highest possible price
     *
     * @return QUI\ERP\Money\Price
     */
    public function getMaximumPrice(): QUI\ERP\Money\Price
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * @return bool|float|int
     */
    public function getMaximumQuantity(): float|bool|int
    {
        return 1;
    }

    /**
     * Return the main category
     *
     * @return QUI\ERP\Products\Category\Category|null
     */
    public function getCategory(): ?QUI\ERP\Products\Category\Category
    {
        return null;
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function hasOfferPrice(): bool
    {
        return false;
    }

    /**
     * Return all images of the product
     *
     * @return Image[]
     */
    public function getImages(): array
    {
        return [];
    }

    //region calc

    public function calc($Calc = null): static
    {
        return $this;
    }

    public function resetCalculation()
    {
        // nothing - placeholder
    }

    //endregion
}
