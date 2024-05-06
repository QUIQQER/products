<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\Locale;

use function array_merge;
use function implode;

/**
 * Product backend view
 *
 * @package QUI\ERP\Products\Product
 */
class ViewBackend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * @var Model|Product<
     */
    protected Model|Product $Product;

    /**
     * View constructor.
     * @param Model $Product
     */
    public function __construct(Model $Product)
    {
        $this->Product = $Product;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->Product->getId();
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'image' => false
        ];

        try {
            $attributes['image'] = $this->getImage()->getUrl(true);
        } catch (QUI\Exception) {
        }


        $Price = $this->getPrice();

        $attributes['price_netto'] = $Price->value();
        $attributes['price_currency'] = $Price->getCurrency()->getCode();

        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        }

        // fields
        $fields = [];
        $fieldList = $this->getFields();

        /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
        foreach ($fieldList as $Field) {
            $fields[] = array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );
        }

        if (!empty($fields)) {
            $attributes['fields'] = $fields;
        }

        // categories
        $categories = [];
        $catList = $this->getCategories();

        /* @var $Category QUI\ERP\Products\Category\Category */
        foreach ($catList as $Category) {
            $categories[] = $Category->getId();
        }

        if (!empty($categories)) {
            $attributes['categories'] = implode(',', $categories);
        }

        return $attributes;
    }

    /**
     * @return Model|Product
     */
    public function getProduct(): Product|Model
    {
        return $this->Product;
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getTitle(QUI\Locale|null $Locale = null): string
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(QUI\Locale|null $Locale = null): string
    {
        return $this->Product->getDescription($Locale);
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getContent(QUI\Locale|null $Locale = null): string
    {
        return $this->Product->getContent($Locale);
    }

    /**
     * @return QUI\ERP\Money\Price
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        return new QUI\ERP\Money\Price(
            $this->getAttribute('price'),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * @return QUI\ERP\Money\Price
     * @throws QUI\Exception
     */
    public function getMinimumPrice(): QUI\ERP\Money\Price
    {
        return $this->Product->getMinimumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * @return QUI\ERP\Money\Price
     * @throws QUI\Exception
     */
    public function getMaximumPrice(): QUI\ERP\Money\Price
    {
        return $this->Product->getMaximumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * @return bool|float|int
     */
    public function getMaximumQuantity(): float|bool|int
    {
        return $this->Product->getMaximumQuantity();
    }

    /**
     * Get a FieldView
     *
     * @param integer $fieldId
     * @return QUI\ERP\Products\Field\View
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getFieldView(int $fieldId): QUI\ERP\Products\Field\View
    {
        return $this->getProduct()->getField($fieldId)->getBackendView();
    }

    /**
     * @param string|array $type
     * @return array
     */
    public function getFieldsByType(string|array $type): array
    {
        return $this->getProduct()->getFieldsByType($type);
    }

    /**
     * @param int $fieldId
     * @return FieldInterface|null
     * @throws Exception
     */
    public function getField(int $fieldId): ?QUI\ERP\Products\Interfaces\FieldInterface
    {
        return $this->getProduct()->getField($fieldId);
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->getProduct()->getFields();
    }

    /**
     * @param int|string $fieldId
     * @return string|array|null
     *
     * @throws Exception
     */
    public function getFieldValue(int|string $fieldId): string|array|null
    {
        return $this->getProduct()->getFieldValue($fieldId);
    }

    /**
     * @return null|QUI\ERP\Products\Category\Category
     */
    public function getCategory(): ?QUI\ERP\Products\Category\Category
    {
        return $this->getProduct()->getCategory();
    }

    /**
     * @return QUI\Projects\Media\Image
     * @throws QUI\Exception
     */
    public function getImage(): QUI\Projects\Media\Image
    {
        return $this->getProduct()->getImage();
    }

    /**
     * @return array|QUI\Projects\Media\Image[]
     */
    public function getImages(): array
    {
        return $this->Product->getImages();
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->getProduct()->getCategories();
    }

    /**
     * @return bool
     */
    public function hasOfferPrice(): bool
    {
        try {
            return $this->getProduct()->hasOfferPrice();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return false|QUI\ERP\Products\Interfaces\UniqueFieldInterface
     */
    public function getOriginalPrice(): QUI\ERP\Products\Interfaces\UniqueFieldInterface|bool
    {
        try {
            return $this->getProduct()->getOriginalPrice();
        } catch (\Exception) {
            return false;
        }
    }

    //region calculation

    /**
     * @param null $Calc
     * @return mixed
     */
    public function calc($Calc = null): mixed
    {
        return $this->getProduct()->calc($Calc);
    }

    /**
     * @return void
     */
    public function resetCalculation(): void
    {
        $this->getProduct()->resetCalculation();
    }

    //endregion
}
