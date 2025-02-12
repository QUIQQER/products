<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\ProductInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Products\Category\Category;
use QUI\ERP\Money\Price;
use QUI\Exception;
use QUI\Locale;
use QUI\Projects\Media\Image;

/**
 * Interface Product
 * @package QUI\ERP\Products
 */
interface ProductInterface
{
    /**
     * Return the Product-ID
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Return all attributes of the product
     *
     * @return mixed
     */
    public function getAttributes(): mixed;

    /**
     * Return the translated title
     *
     * @param ?Locale $Locale
     * @return string
     */
    public function getTitle(null | Locale $Locale = null): string;

    /**
     * Return the translated description
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(null | Locale $Locale = null): string;

    /**
     * Return the translated content
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getContent(null | Locale $Locale = null): string;

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Return the field
     *
     * @param integer $fieldId
     * @return FieldInterface|UniqueFieldInterface|null
     */
    public function getField(int $fieldId): null|FieldInterface|UniqueFieldInterface;

    /**
     * Return the field attribute / value of the product
     *
     * @param integer $fieldId
     * @return mixed
     */
    public function getFieldValue(int $fieldId): mixed;

    /**
     * Return all fields from the wanted type
     *
     * @param array|string $type
     * @return array
     */
    public function getFieldsByType(array|string $type): array;

    /**
     * Return the main product image
     *
     * @return Image
     * @throws Exception
     */
    public function getImage(): Image;

    /**
     * Return all images of the product
     *
     * @return Image[]
     */
    public function getImages(): array;

    /**
     * Return the price object of the product
     *
     * @return Price
     */
    public function getPrice(): Price;

    /**
     * Return the lowest possible price
     *
     * @return Price
     */
    public function getMinimumPrice(): Price;

    /**
     * Return the highest possible price
     *
     * @return Price
     */
    public function getMaximumPrice(): Price;

    /**
     * Return the maximum quantity for this product
     *
     * @return bool|integer|float
     */
    public function getMaximumQuantity(): float|bool|int;

    /**
     * Return an attribute of the product
     *
     * @param string $name - name of the attribute
     * @return mixed
     */
    public function getAttribute(string $name): mixed;

    /**
     * Return the main category
     *
     * @return CategoryInterface|null
     */
    public function getCategory(): ?CategoryInterface;

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Has the product an offer price
     *
     * @return bool
     */
    public function hasOfferPrice(): bool;

    /**
     * Return the original price, not the offer price
     *
     * @return false|UniqueFieldInterface|Price
     */
    public function getOriginalPrice(): UniqueFieldInterface|Price|bool;
}
