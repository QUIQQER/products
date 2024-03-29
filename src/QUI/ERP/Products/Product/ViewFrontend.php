<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */

namespace QUI\ERP\Products\Product;

use QUI;
use Symfony\Component\HttpFoundation\Response;

use function array_merge;

/**
 * Product frontend View
 *
 * @package QUI\ERP\Products\Product
 */
class ViewFrontend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * @var UniqueProduct
     */
    protected $Product;

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
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->Product->getId();
    }

    /**
     * @return array
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getAttributes()
    {
        $attributes = [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'image' => false
        ];

        try {
            $Image = $this->getImage();

            if ($Image) {
                $attributes['image'] = $this->getImage()->getUrl(true);
            }
        } catch (QUI\Exception $Exception) {
        }


        /* @var $Price QUI\ERP\Money\Price */
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
            $attributes['categories'] = \implode(',', $categories);
        }

        return $attributes;
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getTitle($Locale = false)
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false)
    {
        return $this->Product->getDescription($Locale);
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false)
    {
        return $this->Product->getContent($Locale);
    }

    /**
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getPrice()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return new QUI\ERP\Money\Price(
                '',
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
    public function getPriceDisplay()
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
    public function getMinimumPrice()
    {
        return $this->Product->getMinimumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * @return QUI\ERP\Money\Price
     * @throws QUI\Exception
     */
    public function getMaximumPrice()
    {
        return $this->Product->getMaximumPrice(
            QUI::getUserBySession()
        );
    }

    /**
     * @return bool|float|int|mixed
     */
    public function getMaximumQuantity()
    {
        return $this->Product->getMaximumQuantity();
    }

    /**
     * Get value of field
     *
     * @param integer $fieldId
     * @param bool $affixes (optional) - append suffix and prefix if defined [default: false]
     * @return mixed - formatted field value
     */
    public function getFieldValue($fieldId, $affixes = false)
    {
        $Field = $this->getField($fieldId);

        return $Field && $Field->isPublic() ? $Field->getValue() : false;
    }

    /**
     * Return all fields from the wanted type
     *
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        $types = $this->Product->getFieldsByType($type);

        $types = \array_filter($types, function ($Field) {
            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            return $Field->isPublic();
        });

        return $types;
    }

    /**
     * Return the the wanted field
     *
     * @param int $fieldId
     * @return false|QUI\ERP\Products\Field\UniqueField|QUI\ERP\Products\Interfaces\FieldInterface
     */
    public function getField($fieldId)
    {
        try {
            $Field = $this->Product->getField($fieldId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return false;
        }


        if ($Field->getId() === QUI\ERP\Products\Handler\Fields::FIELD_CONTENT) {
            return $Field;
        }

        return $Field->isPublic() ? $Field : false;
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        $fields = $this->Product->getFields();

        $fields = \array_filter($fields, function ($Field) {
            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            return $Field->isPublic();
        });

        return $fields;
    }

    /**
     * Return the main category
     *
     * @return QUI\ERP\Products\Category\Category
     */
    public function getCategory()
    {
        return $this->Product->getCategory();
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->Product->getCategories();
    }

    /**
     * Return the product image
     *
     * @return QUI\Projects\Media\Image
     *
     * @throws QUI\Exception
     */
    public function getImage()
    {
        try {
            $Image = $this->Product->getImage();

            if ($Image->isActive()) {
                return $this->Product->getImage();
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Folder = $this->Product->getMediaFolder();

            if ($Folder) {
                $images = $Folder->getImages([
                    'limit' => 1,
                    'order' => 'priority ASC'
                ]);

                if (isset($images[0]) && $images[0]->isActive()) {
                    return $images[0];
                }
            }
        } catch (QUI\Exception $Exception) {
        }

        return QUI::getRewrite()->getProject()->getMedia()->getPlaceholderImage();
    }

    /**
     * @return array|QUI\Projects\Media\Image[]
     */
    public function getImages()
    {
        return $this->Product->getImages();
    }

    /**
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrl()
    {
        return $this->Product->getUrl();
    }

    /**
     * @return bool
     */
    public function hasOfferPrice()
    {
        return $this->Product->hasOfferPrice();
    }

    /**
     * @return false|QUI\ERP\Products\Interfaces\UniqueFieldInterface
     */
    public function getOriginalPrice()
    {
        return $this->Product->getOriginalPrice();
    }

    /**
     * Return a calculated price field
     *
     * @param integer $FieldId
     * @return false|QUI\ERP\Products\Field\UniqueField
     */
    public function getCalculatedPrice($FieldId)
    {
        return $this->Product->getCalculatedPrice($FieldId);
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
    public function calc($Calc = null)
    {
        return $this->Product->calc($Calc);
    }

    /**
     * @param null $Calc
     * @return mixed
     */
    public function resetCalculation()
    {
        return $this->Product->resetCalculation();
    }

    //endregion
}
