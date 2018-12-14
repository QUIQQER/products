<?php

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Handler\Fields;

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
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'displayPrice' => false
        ]);

        $this->setAttributes($attributes);
    }

    /**
     * @param null $User
     * @return UniqueProduct
     * @throws Exception
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     * @throws QUI\Users\Exception
     */
    public function createUniqueProduct($User = null)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        $attributes                 = $this->getAttributes();
        $attributes['title']        = $this->getTitle();
        $attributes['description']  = $this->getDescription();
        $attributes['uid']          = $User->getId();
        $attributes['displayPrice'] = false;

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

        return new UniqueProduct($this->getId(), $attributes);
    }

    /**
     * Return the Product-ID
     *
     * @return integer|string
     */
    public function getId()
    {
        return '-';
    }

    /**
     * Return the translated title
     *
     * @param bool $Locale
     * @return string
     */
    public function getTitle($Locale = false)
    {
        if (!$this->existsAttribute('title')) {
            return '';
        }

        return $this->getAttribute('title');
    }

    /**
     * Return the translated description
     *
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false)
    {
        if (!$this->existsAttribute('description')) {
            return '';
        }

        return $this->getAttribute('description');
    }

    /**
     * Return the translated content
     *
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false)
    {
        return '';
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        $fields = [];

        $addField = function ($fieldId) use (&$fields) {
            try {
                $fields[] = $this->getField($fieldId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
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
     * @return QUI\ERP\Products\Interfaces\FieldInterface
     * @throws QUI\Exception
     */
    public function getField($fieldId)
    {
        switch ((int)$fieldId) {
            case Fields::FIELD_PRICE:
                return new QUI\ERP\Products\Field\Types\Price($fieldId, [
                    'value' => $this->getPrice()
                ]);

            case Fields::FIELD_VAT:
                return new QUI\ERP\Products\Field\Types\Vat($fieldId, [
                    'value'        => $this->getAttribute('vat'),
                    'defaultValue' => $this->getAttribute('vat')
                ]);

            case Fields::FIELD_CONTENT:
                return new QUI\ERP\Products\Field\Types\Textarea($fieldId, [
                    'value' => ''
                ]);
        }


        $Field = new QUI\ERP\Products\Field\Types\Input($fieldId);

        switch ((int)$fieldId) {
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
    public function getFieldValue($fieldId)
    {
        throw new QUI\ERP\Products\Product\Exception([
            'quiqqer/products',
            'exception.field.not.found',
            [
                'fieldId'   => $fieldId,
                'productId' => $this->getId()
            ]
        ], 1002);
    }

    /**
     * Return all fields from the wanted type
     *
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        return [];
    }

    /**
     * Return the main product image
     *
     * @return \QUI\Projects\Media\Image
     * @throws \QUI\Exception
     */
    public function getImage()
    {
        return QUI::getRewrite()->getProject()->getMedia()->getPlaceholderImage();
    }

    /**
     * Return the price object of the product
     *
     * @return QUI\ERP\Money\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * @return false|QUI\ERP\Money\Price|QUI\ERP\Products\Interfaces\UniqueFieldInterface
     */
    public function getOriginalPrice()
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * @return false|QUI\ERP\Money\Price|QUI\ERP\Products\Interfaces\UniqueFieldInterface
     */
    public function getOfferPrice()
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * Return the lowest possible price
     *
     * @return QUI\ERP\Money\Price
     */
    public function getMinimumPrice()
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * Return the highest possible price
     *
     * @return QUI\ERP\Money\Price
     */
    public function getMaximumPrice()
    {
        return new QUI\ERP\Money\Price(0, QUI\ERP\Defaults::getCurrency());
    }

    /**
     * Return the main category
     *
     * @return QUI\ERP\Products\Category\Category|null
     */
    public function getCategory()
    {
        return null;
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories()
    {
        return [];
    }

    //region calc

    public function calc($Calc = null)
    {
        return $this;
    }

    public function resetCalculation()
    {
        // nothing - placeholder
    }

    //endregion
}
