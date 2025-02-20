<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\Database\Exception;
use QUI\ERP\Products\Category\Category;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Interfaces\UniqueFieldInterface;
use QUI\Locale;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;
use function array_merge;
use function get_class;
use function implode;

/**
 * Product frontend View
 *
 * @package QUI\ERP\Products\Product
 */
class ViewFrontend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * @var Model|UniqueProduct
     */
    protected Model | UniqueProduct $Product;

    /**
     * View constructor.
     *
     * @param Model $Product
     * @throws QUI\Permissions\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function __construct(Model $Product)
    {
        $this->Product = $Product;

        if (!$Product->isActive()) {
            throw new QUI\ERP\Products\Product\Exception(
                [
                    'quiqqer/products',
                    'exception.product.not.found',
                    ['productId' => $this->getId()]
                ],
                404,
                [
                    'id' => $this->getId(),
                    'view' => 'frontend',
                    'active' => 0
                ]
            );
        }

        if (!QUI\ERP\Products\Handler\Products::usePermissions()) {
            return;
        }

        $permissions = $this->Product->getPermissions();

        if (!isset($permissions['permission.viewable'])) {
            return;
        }

        // check group in list
        $isAllowed = QUI\Utils\UserGroups::isUserInUserGroupString(
            QUI::getUserBySession(),
            $permissions['permission.viewable']
        );

        if ($isAllowed === false) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * @return Model|UniqueProduct
     */
    public function getProduct(): UniqueProduct | Model
    {
        return $this->Product;
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
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Product\Exception
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
        $fieldList = $this->getFields(); // only public fields

        /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
        foreach ($fieldList as $Field) {
            if (!$Field->isPublic()) {
                continue;
            }

            $fields[] = array_merge(
                $Field->toProductArray(),
                $Field->getAttributes()
            );
        }

        // fields -> BasketConditions
        $conditions = $this->Product->getFieldsByType('BasketConditions');

        foreach ($conditions as $Field) {
            if ($Field->isPublic()) {
                // are already added
                continue;
            }

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
     * @param QUI\Locale|null $Locale
     * @return string
     */
    public function getTitle(null | QUI\Locale $Locale = null): string
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(null | QUI\Locale $Locale = null): string
    {
        return $this->Product->getDescription($Locale);
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getContent(null | QUI\Locale $Locale = null): string
    {
        return $this->Product->getContent($Locale);
    }

    /**
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Money\Price(
                null,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        $User = QUI::getUserBySession();
        $price = $this->Product->getPrice()->getPrice();

        if ($price === null || $this->Product instanceof QUI\ERP\Products\Product\Types\VariantParent) {
            $Price = $this->getProduct()->getMinimumPrice($User);
        } else {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);

            $Price = $Calc->getProductPrice(
                $this->Product->createUniqueProduct($User)
            );
        }

        // use search cache
        $minCache = 'quiqqer/products/' . $this->getId() . '/prices/min';
        $maxName = 'quiqqer/products/' . $this->getId() . '/prices/max';

        $min = null;
        $max = null;

        try {
            $min = QUI\Cache\LongTermCache::get($minCache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        try {
            $max = QUI\Cache\LongTermCache::get($maxName);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        if ($min === null || $max === null) {
            $priceResult = QUI::getDataBase()->fetch([
                'select' => 'id, minPrice, maxPrice',
                'from' => QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
                'where' => [
                    'id' => $this->getId()
                ],
                'limit' => 1
            ]);

            if (isset($priceResult[0])) {
                $min = $priceResult[0]['minPrice'];
                $max = $priceResult[0]['maxPrice'];
            } else {
                $min = $this->Product->getMinimumPrice();
                $max = $this->Product->getMaximumPrice();
            }
        }

        if ($min !== $max) {
            $Price->enableMinimalPrice();
        }

        return $Price;
    }

    /**
     * Return the price display for the product
     *
     * @return QUI\ERP\Products\Controls\Price
     *
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getPriceDisplay(): QUI\ERP\Products\Controls\Price
    {
        $Price = $this->getPrice();
        $vatArray = [];

        $User = QUI::getUserBySession();
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);
        $Product = $this->getProduct();

        if (!($Product instanceof UniqueProduct)) {
            $Product = $Product->createUniqueProduct($User);
        }

        $Product->calc($Calc);
        $attributes = $Product->getAttributes();

        if (isset($attributes['calculated_vatArray'])) {
            $vatArray = $attributes['calculated_vatArray'];
        }

        return new QUI\ERP\Products\Controls\Price([
            'Price' => $Price,
            'withVatText' => true,
            'Calc' => $Calc,
            'vatArray' => $vatArray
        ]);
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
     * @return float|bool|int
     */
    public function getMaximumQuantity(): float | bool | int
    {
        return $this->Product->getMaximumQuantity();
    }

    /**
     * Get value of field
     *
     * @param int|string $fieldId
     * @return mixed - formatted field value
     */
    public function getFieldValue(int | string $fieldId): mixed
    {
        $Field = $this->getField($fieldId);

        return $Field && $Field->isPublic() ? $Field->getValue() : false;
    }

    /**
     * Return all fields from the wanted type
     *
     * @param string|array $type
     * @return array
     */
    public function getFieldsByType(string | array $type): array
    {
        $types = $this->Product->getFieldsByType($type);

        return array_filter($types, function ($Field) {
            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            return $Field->isPublic();
        });
    }

    /**
     * Return the wanted field
     *
     * @param int|string $fieldId
     * @return FieldInterface|null
     */
    public function getField(int | string $fieldId): ?QUI\ERP\Products\Interfaces\FieldInterface
    {
        try {
            $Field = $this->Product->getField($fieldId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return null;
        }

        if (!($Field instanceof QUI\ERP\Products\Interfaces\FieldInterface)) {
            QUI\System\Log::addError(
                'Wrong instance return at QUI\ERP\Products\Product\ViewFrontend',
                [
                    'return type' => get_class($Field),
                    'line' => 402
                ]
            );

            return null;
        }

        if ($Field->getId() === QUI\ERP\Products\Handler\Fields::FIELD_CONTENT) {
            return $Field;
        }

        return $Field->isPublic() ? $Field : null;
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = $this->Product->getFields();

        return array_filter($fields, function ($Field) {
            return $Field->isPublic();
        });
    }

    /**
     * Return the main category
     */
    public function getCategory(): ?QUI\ERP\Products\Interfaces\CategoryInterface
    {
        return $this->Product->getCategory();
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        return $this->Product->getCategories();
    }

    /**
     * Return the product image
     *
     * @return QUI\Projects\Media\Image
     * @throws QUI\Exception
     */
    public function getImage(): QUI\Projects\Media\Image
    {
        try {
            $Image = $this->Product->getImage();

            if ($Image->isActive()) {
                return $this->Product->getImage();
            }
        } catch (QUI\Exception) {
        }

        try {
            $Folder = $this->Product->getMediaFolder();
            $images = $Folder->getImages([
                'limit' => 1,
                'order' => 'priority ASC'
            ]);

            if (isset($images[0]) && $images[0]->isActive()) {
                return $images[0];
            }
        } catch (QUI\Exception) {
        }

        $Placeholder = QUI::getRewrite()->getProject()->getMedia()->getPlaceholderImage();

        if ($Placeholder instanceof QUI\Projects\Media\Image) {
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
     * @return array|QUI\Projects\Media\Image[]
     */
    public function getImages(): array
    {
        return $this->Product->getImages();
    }

    /**
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrl(): string
    {
        return $this->Product->getUrl();
    }

    /**
     * @return bool
     */
    public function hasOfferPrice(): bool
    {
        return $this->Product->hasOfferPrice();
    }

    /**
     * @return false|UniqueFieldInterface
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getOriginalPrice(): QUI\ERP\Products\Interfaces\UniqueFieldInterface | bool
    {
        return $this->Product->getOriginalPrice();
    }

    /**
     * Return a calculated price field
     *
     * @param integer $FieldId
     * @return ?UniqueField
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getCalculatedPrice(int $FieldId): ?QUI\ERP\Products\Field\UniqueField
    {
        $Field = $this->Product->getCalculatedPrice($FieldId);

        if ($Field instanceof QUI\ERP\Products\Field\UniqueField) {
            return $Field;
        }

        QUI\System\Log::addError(
            'Wring return value at getCalculatedPrice()',
            [
                'class' => ViewFrontend::class,
                'line' => 545
            ]
        );

        return null;
    }

    /**
     * Check if this product has fields that require user input.
     *
     * @return bool
     */
    public function hasRequiredUserInputFields(): bool
    {
        foreach ($this->Product->getFields() as $Field) {
            if (
                $Field instanceof QUI\ERP\Products\Field\CustomInputFieldInterface &&
                $Field->isRequired()
            ) {
                return true;
            }
        }

        return false;
    }

    //region calculation

    /**
     * @param null $Calc
     *
     * @return mixed|UniqueProduct
     *
     * @throws QUI\Exception
     * @throws QUI\Users\Exception
     */
    public function calc($Calc = null): mixed
    {
        return $this->Product->calc($Calc);
    }

    /**
     * @return void
     */
    public function resetCalculation(): void
    {
        $this->Product->resetCalculation();
    }

    //endregion
}
