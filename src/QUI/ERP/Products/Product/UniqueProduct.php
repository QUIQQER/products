<?php

/**
 * This file contains QUI\ERP\Products\Product\UniqueProduct
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Accounting\ArticleInterface;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Utils\PriceFactor;
use QUI\ERP\Accounting\Calc as ErpCalc;

use QUI\Projects\Media\Utils as MediaUtils;

/**
 * Class UniqueProduct
 *
 * @event onQuiqqerProductsPriceFactorsInit [
 *      QUI\ERP\Products\Utils\PriceFactors,
 *      QUI\ERP\Products\Interfaces\ProductInterface
 * ]
 * @todo view für unique product
 */
class UniqueProduct extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * is the product list calculated?
     * @var bool
     */
    protected $calculated = false;

    /**
     * @var integer|string
     */
    protected $id;

    /**
     * User-ID
     *
     * @var
     */
    protected $uid;

    /**
     * @var array
     */
    protected $userData = [];

    /**
     * @var integer|float
     */
    protected $quantity = 1;

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var null|QUI\ERP\Products\Category\Category
     */
    protected $Category = null;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * Price factors
     *
     * @var QUI\ERP\Products\Utils\PriceFactors
     */
    protected $PriceFactors;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * calculated price
     * @var float|int
     */
    protected $price;

    /**
     * calculated basisprice - netto or brutto
     *
     * @var float|int
     */
    protected $basisPrice;

    /**
     * calculated basisprice - netto or brutto - no round
     *
     * @var float|int
     */
    protected $nettoPriceNotRounded;

    /**
     * calculated basisprice - netto or brutto - no round
     *
     * @var float|int
     */
    protected $nettoSumNotRounded;

    /**
     * calculated sum
     * @var float|int
     */
    protected $sum;

    /**
     * @var float|int
     */
    protected $minimumPrice;

    /**
     * @var float|int
     */
    protected $maximumPrice;

    /**
     * @var mixed
     */
    protected $maximumQuantity = false;

    /**
     * calculated nettosum
     * @var float|int
     */
    protected $nettoSum;

    /**
     * Netto price
     *
     * @var float|int
     */
    protected $nettoPrice;

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array
     */
    protected $vatArray = [];

    /**
     * Prüfen ob EU Vat für den Benutzer in Frage kommt
     * @var
     */
    protected $isEuVat = false;

    /**
     * Wird Brutto oder Netto gerechnet
     * @var bool
     */
    protected $isNetto = true;

    /**
     * Calculated factors
     * @var array
     */
    protected $factors = [];

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected $Currency = null;

    /**
     * UniqueProduct constructor.
     *
     * @param integer $pid - Product ID
     * @param $attributes - attributes
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function __construct($pid, $attributes = [])
    {
        $this->id         = $pid;
        $this->attributes = $attributes;
        $this->Currency   = QUI\ERP\Defaults::getCurrency();

        if (!isset($attributes['uid'])) {
            throw new QUI\ERP\Products\Product\Exception([
                'quiqqer/products',
                'exception.missing.uid.unique.product'
            ]);
        }

        if (isset($attributes['minimumPrice'])) {
            $this->minimumPrice = $attributes['minimumPrice'];
        }

        if (isset($attributes['maximumPrice'])) {
            $this->maximumPrice = $attributes['maximumPrice'];
        }

        if (isset($attributes['maximumQuantity'])) {
            $this->maximumQuantity = $attributes['maximumQuantity'];
        }

        if (isset($attributes['price_currency'])) {
            try {
                $this->Currency = QUI\ERP\Currency\Handler::getCurrency($attributes['price_currency']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        $this->uid = (int)$attributes['uid'];


        // fields
        $this->parseFieldsFromAttributes($attributes);
        $this->parseCategoriesFromAttributes($attributes);

        // generate the price factors
        $fields = $this->getFields();

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
//            if (!$Field->isCustomField()) {
//                continue;
//            }

            if (!($Field instanceof QUI\ERP\Products\Field\CustomField)) {
                continue;
            }

            $FieldView = $Field->getView();

            $attributes       = $Field->getAttributes();
            $factorAttributes = $attributes['custom_calc'];

            $factorAttributes['visible'] = $FieldView->hasViewPermission($this->getUser());

            $Factor = new PriceFactor($factorAttributes);
            $Factor->setTitle($Field->getTitle());

            $this->PriceFactors->add($Factor);
        }

        if (isset($attributes['quantity']) && (int)$attributes['quantity']) {
            $this->setQuantity((int)$attributes['quantity']);
        }

        QUI::getEvents()->fireEvent(
            'quiqqerProductsPriceFactorsInit',
            [$this->PriceFactors, $this]
        );

        $this->recalculation();
    }

    /**
     * Parse the field data
     *
     * @param array $attributes - product attributes
     */
    protected function parseFieldsFromAttributes($attributes = [])
    {
        if (!isset($attributes['fields'])) {
            return;
        }

        $fields = $attributes['fields'];

        try {
            QUI::getEvents()->fireEvent(
                'quiqqerProductsUniqueProductParseFields',
                [$this, &$fields]
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        foreach ($fields as $field) {
            if (!Fields::isField($field)) {
                $this->fields[] = new UniqueField($field['id'], $field);
                continue;
            }

            if (\get_class($field) != UniqueField::class) {
                /* @var $field QUI\ERP\Products\Field\Field */
                $field = $field->createUniqueField();
            }

            $this->fields[] = $field;
        }
    }

    /**
     * Parse the category data
     *
     * @param array $attributes
     */
    protected function parseCategoriesFromAttributes($attributes = [])
    {
        if (!isset($attributes['categories'])) {
            return;
        }

        $list       = [];
        $categories = \explode(',', $attributes['categories']);

        foreach ($categories as $cid) {
            try {
                $list[] = Categories::getCategory($cid);
            } catch (QUI\Exception $Exception) {
            }
        }

        $this->categories = $list;
    }

    /**
     * @param array $attributes
     */
    protected function parseCategoryFromAttributes($attributes = [])
    {
        if (!isset($attributes['category'])) {
            return;
        }

        try {
            $this->Category = QUI\ERP\Products\Handler\Categories::getCategory(
                $attributes['category']
            );
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Return the user for the unique product
     *
     * @return QUI\Users\User
     * @throws QUI\Users\Exception
     */
    public function getUser()
    {
        return QUI::getUsers()->get($this->uid);
    }

    /**
     * Return the price factor list of the product
     *
     * @return QUI\ERP\Products\Utils\PriceFactors
     */
    public function getPriceFactors()
    {
        return $this->PriceFactors;
    }

    /**
     * Unique identifier
     *
     * @return string
     */
    public function getCacheIdentifier()
    {
        return \md5(\serialize($this->getAttributes()));
    }

    /**
     * Return the Product-ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return UniqueProduct|UniqueProductFrontendView
     *
     * @throws QUI\Exception
     */
    public function getView()
    {
        $this->calc();

        if (QUI::isBackend()) {
            return $this;
        }

        $attributes                    = $this->getAttributes();
        $attributes['uid']             = $this->getUser()->getId();
        $attributes['maximumQuantity'] = $this->getMaximumQuantity();

        return new UniqueProductFrontendView($this->id, $attributes);
    }

    //region calculation

    /**
     * Calculates
     *
     * @param QUI\ERP\Products\Utils\Calc|null $Calc - optional, calculation object
     * @return UniqueProduct
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function calc($Calc = null)
    {
        if ($this->calculated) {
            return $this;
        }

        $self = $this;

        if (!$Calc) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance($this->getUser());
        }

        $Calc->getProductPrice($this, function ($data) use ($self) {
            $self->price      = $data['price'];
            $self->basisPrice = $data['basisPrice'];
            $self->sum        = $data['sum'];
            $self->nettoPrice = $data['nettoPrice'];
            $self->nettoSum   = $data['nettoSum'];
            $self->vatArray   = $data['vatArray'];
            $self->isEuVat    = $data['isEuVat'];
            $self->isNetto    = $data['isNetto'];
            $self->factors    = $data['factors'];

            $self->nettoPriceNotRounded = $data['nettoPriceNotRounded'];
            $self->nettoSumNotRounded   = $data['nettoSumNotRounded'];

            $self->calculated = true;
        });

        return $this;
    }

    /**
     * @return mixed|void
     */
    public function resetCalculation()
    {
        $this->calculated = false;
    }

    /**
     * @param null $Calc
     *
     * @return UniqueProduct
     *
     * @throws QUI\Exception
     * @throws QUI\Users\Exception
     */
    public function recalculation($Calc = null)
    {
        QUI::getEvents()->fireEvent('quiqqerProductsUniqueProductRecalculation', [$this]);

        $this->resetCalculation();

        return $this->calc($Calc);
    }

    /**
     * Convert all prices to the new currency
     *
     * @param QUI\ERP\Currency\Currency $Currency
     *
     * @todo save original price
     */
    public function convert(QUI\ERP\Currency\Currency $Currency)
    {
        if ($this->Currency->getCode() === $Currency->getCode()) {
            return;
        }

        try {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance($this->getUser());
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return;
        }


        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($this->fields as $key => $Field) {
            if ($Field->getType() !== FieldHandler::TYPE_PRICE
                && $Field->getType() !== FieldHandler::TYPE_PRICE_BY_QUANTITY
                && $Field->getType() !== FieldHandler::TYPE_PRICE_BY_TIMEPERIOD) {
                continue;
            }

            $value = $Field->getValue();

            if (empty($value)) {
                continue;
            }

            try {
                $value = $this->Currency->convert($value, $Currency);
                $value = $Calc->round($value);
                $value = $Currency->amount($value);

                $OriginalField = Fields::getField($Field->getId());
                $OriginalField->setValue($value);
            } catch (QUI\Exception $Exception) {
                continue;
            }

            $this->fields[$key] = $OriginalField->createUniqueField();
        }

        $priceFactors = $this->getPriceFactors()->sort();

        /* @var $PriceFactor PriceFactor */
        foreach ($priceFactors as $PriceFactor) {
            if ($PriceFactor->getCalculation() === ErpCalc::CALCULATION_COMPLEMENT) {
                try {
                    $value = $PriceFactor->getValue();
                    $value = $this->Currency->convert($value, $Currency);
                    $value = $Calc->round($value);
                    $value = $Currency->amount($value);

                    $PriceFactor->setValue($value);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }

        try {
            $this->recalculation();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * @return QUI\ERP\Currency\Currency|null
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    //endregion

    /**
     * Return the translated title
     *
     * @param bool|\QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title   = $this->getField(Fields::FIELD_TITLE);

        if (!$Title) {
            return '';
        }

        $values = $Title->getValue();

        if (\is_string($values)) {
            return $values;
        }

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     * Return the translated description
     *
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $current     = $Locale->getCurrent();
        $Description = $this->getField(Fields::FIELD_SHORT_DESC);
        $values      = $Description->getValue();

        if (\is_string($values)) {
            return $values;
        }

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     * Return the translated content
     *
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title   = $this->getField(Fields::FIELD_CONTENT);

        if (!$Title) {
            return '';
        }

        $values = $Title->getValue();

        if (\is_string($values)) {
            return $values;
        }

        return isset($values[$current]) ? $values[$current] : '';
    }

    /**
     * Return the image url
     *
     * @return QUI\Projects\Media\Image
     * @throws QUI\Exception
     */
    public function getImage()
    {
        $image = $this->getFieldValue(Fields::FIELD_IMAGE);

        try {
            return MediaUtils::getImageByUrl($image);
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Folder = MediaUtils::getMediaItemByUrl(
                $this->getFieldValue(Fields::FIELD_FOLDER)
            );

            /* @var $Folder QUI\Projects\Media\Folder */
            if (MediaUtils::isFolder($Folder)) {
                $images = $Folder->getImages([
                    'limit' => 1
                ]);

                if (isset($images[0])) {
                    return $images[0];
                }
            }
        } catch (QUI\Exception $Exception) {
        }

        try {
            $Project     = QUI::getRewrite()->getProject();
            $Media       = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder) {
                return $Placeholder;
            }
        } catch (QUI\Exception $Exception) {
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
    public function getImages()
    {
        try {
            $Folder = MediaUtils::getMediaItemByUrl(
                $this->getFieldValue(Fields::FIELD_FOLDER)
            );

            if (MediaUtils::isFolder($Folder)) {
                return $Folder->getImages();
            }
        } catch (QUI\Exception $Exception) {
        }

        return [];
    }

    /**
     * Return the the wanted field
     *
     * @param int $fieldId
     * @return QUI\ERP\Products\Field\UniqueField|false
     */
    public function getField($fieldId)
    {
        $fields = $this->getFields();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            if ($Field->getId() == $fieldId) {
                return $Field;
            }
        }

        return false;
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        $fields = $this->getFields();
        $result = [];

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            if ($Field && $Field->getType() == $type) {
                $result[] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getPrice()
    {
        $this->calc();

        $Price = new QUI\ERP\Money\Price(
            $this->sum,
            QUI\ERP\Currency\Handler::getDefaultCurrency(),
            $this->getUser()
        );

        // wenn attribute listen existieren
        // dann muss der kleinste preis rausgefunden werden
        // d.h. bei attribute listen wird der kleinste preis ausgewählt
        $attributesLists = $this->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST);

        if (!\count($attributesLists)) {
            return $Price;
        }

        // quiqqer/products#292
        if ($this->minimumPrice &&
            $this->maximumPrice &&
            $this->minimumPrice !== $this->maximumPrice) {
            $Price->enableMinimalPrice();
        }
//
//        foreach ($attributesLists as $List) {
//            /* @var $List UniqueField */
//            if ($List->isRequired() && $List->getValue() === '') {
//                $Price->enableMinimalPrice();
//
//                return $Price;
//            }
//        }

        return $Price;
    }

    /**
     * Has the product an offer price
     *
     * @return bool
     */
    public function hasOfferPrice()
    {
        $OfferPrice = $this->getField(Fields::FIELD_PRICE_OFFER);

        if (!$OfferPrice) {
            return false;
        }

        $value = $OfferPrice->getValue();

        if ($value === false) {
            return false;
        }

        return $value !== '';
    }

    /**
     * @return false|UniqueField
     */
    public function getOriginalPrice()
    {
        return $this->getCalculatedPrice(Fields::FIELD_PRICE);
    }

    /**
     * @param $FieldId
     * @return false|UniqueField
     */
    public function getCalculatedPrice($FieldId)
    {
        $Calc         = QUI\ERP\Products\Utils\Calc::getInstance();
        $calculations = [];

        $Field = $this->getField($FieldId);

        try {
            $Calc->getProductPrice($this, function ($calcResult) use (&$calculations) {
                $calculations = $calcResult;
            }, $this->getField($FieldId));
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return $Field;
        }

        $priceAttributes          = $Field->getAttributes();
        $priceAttributes['value'] = $calculations['sum'];

        $PriceField = new UniqueField(
            $Field->getId(),
            $priceAttributes
        );

        return $PriceField;
    }

    /**
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getMinimumPrice()
    {
        if ($this->minimumPrice) {
            return new QUI\ERP\Money\Price(
                $this->minimumPrice,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return $this->getPrice();
    }

    /**
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     * @throws QUI\Users\Exception
     */
    public function getMaximumPrice()
    {
        if ($this->maximumPrice) {
            return new QUI\ERP\Money\Price(
                $this->maximumPrice,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return $this->getPrice();
    }

    /**
     * @return bool|float|int|mixed
     */
    public function getMaximumQuantity()
    {
        return $this->maximumQuantity;
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getUnitPrice()
    {
        $this->calc();

        return new QUI\ERP\Money\Price(
            $this->nettoPrice,
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * Return the netto price of the product
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getNettoPrice()
    {
        return QUI\ERP\Products\Utils\Products::getPriceFieldForProduct($this, $this->getUser());
    }

    /**
     * Return the value of the wanted field
     *
     * @param int $fieldId
     * @return mixed|false
     */
    public function getFieldValue($fieldId)
    {
        $Field = $this->getField($fieldId);

        if ($Field) {
            return $Field->getValue();
        }

        return false;
    }

    /**
     * Return all custom fields
     * custom fields are only fields that the customer fills out
     *
     * @return array
     */
    public function getCustomFields()
    {
        $result = [];

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($this->fields as $Field) {
            if ($Field->isCustomField()) {
                $result[$Field->getId()] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return all public fields
     * custom fields are only fields that the customer fills out
     *
     * @return array
     */
    public function getPublicFields()
    {
        $result = [];

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($this->fields as $Field) {
            if ($Field->isPublic()) {
                $result[$Field->getId()] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return the main category
     *
     * @return QUI\ERP\Products\Category\Category|null
     */
    public function getCategory()
    {
        if ($this->Category) {
            return $this->Category;
        }

        if (!isset($this->attributes['category'])) {
            return $this->Category;
        }

        try {
            $this->Category = Categories::getCategory($this->attributes['category']);
        } catch (QUI\Exception $Exception) {
        }

        return $this->Category;
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }


    /**
     * Set the quantity of the product
     *
     * @param integer|float $quantity
     */
    public function setQuantity($quantity)
    {
        if (!\is_numeric($quantity)) {
            return;
        }

        $quantity = \floatval($quantity);
        $max      = $this->getMaximumQuantity();

        if ($quantity < 0) {
            $quantity = 0;
        }

        if ($max && $max < $quantity) {
            $quantity = $this->getMaximumQuantity();
        }

        $this->quantity = \floatval($quantity);

        try {
            $this->recalculation();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }
    }

    /**
     * Return the quantity
     *
     * @reutrn integer|float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Return the product attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['title']           = $this->getTitle();
        $attributes['description']     = $this->getDescription();
        $attributes['quantity']        = $this->getQuantity();
        $attributes['id']              = $this->getId();
        $attributes['fields']          = $this->getFields();
        $attributes['uid']             = $this->uid;
        $attributes['image']           = '';
        $attributes['maximumQuantity'] = $this->getMaximumQuantity();

        $Price = $this->getOriginalPrice();

        $attributes['hasOfferPrice'] = $this->hasOfferPrice();

        if ($Price instanceof UniqueField) {
            $attributes['originalPrice'] = $Price->getValue();
        } elseif ($Price instanceof QUI\ERP\Money\Price) {
            /* @var $Price QUI\ERP\Money\Price */
            $attributes['originalPrice'] = $Price->getPrice();
        } elseif ($Price instanceof QUI\ERP\Products\Field\UniqueField) {
            /* @var $Price QUI\ERP\Products\Field\UniqueField */
            $attributes['originalPrice'] = $Price->getPrice()->getPrice();
        }


        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        } else {
            try {
                $attributes['category'] = Categories::getMainCategory()->getId();
            } catch (QUI\Exception $Exception) {
                $attributes['category'] = 0;
            }
        }

        // image
        try {
            $Image = $this->getImage();
        } catch (QUI\Exception $Exception) {
            $Image = null;
        }


        if ($Image) {
            $attributes['image'] = $Image->getUrl(true);
        }

        $attributes['calculated_price']    = $this->price;
        $attributes['calculated_sum']      = $this->sum;
        $attributes['calculated_nettoSum'] = $this->nettoSum;
        $attributes['calculated_isEuVat']  = $this->isEuVat;
        $attributes['calculated_isNetto']  = $this->isNetto;
        $attributes['calculated_vatArray'] = $this->vatArray;
        $attributes['calculated_factors']  = $this->factors;

        $attributes['calculated_basisPrice']           = $this->basisPrice;
        $attributes['calculated_nettoPriceNotRounded'] = $this->nettoPriceNotRounded;
        $attributes['calculated_nettoSumNotRounded']   = $this->nettoSumNotRounded;

        $attributes['user_data'] = $this->userData;

        if (isset($attributes['fieldData'])) {
            unset($attributes['fieldData']);
        }

        return $attributes;
    }

    /**
     * Alias for getAttributes()
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAttributes();

        try {
            $Price = $this->getPrice();

            $attributes['price_display']    = $Price->getDisplayPrice();
            $attributes['price_is_minimal'] = $Price->isMinimalPrice();
        } catch (QUI\Exception $Exception) {
            $attributes['price_display']    = false;
            $attributes['price_is_minimal'] = false;
        }

        return $attributes;
    }

    /**
     * Return the unique product as an ERP Article
     *
     * @param null|QUI\Locale $Locale
     * @param bool $fieldsAreChangeable - default = true
     *
     * @return QUI\ERP\Accounting\Article
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function toArticle($Locale = null, $fieldsAreChangeable = true)
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $article = [
            'id'                   => $this->getId(),
            'articleNo'            => $this->getFieldValue(Fields::FIELD_PRODUCT_NO),
            'title'                => $this->getTitle($Locale),
            'description'          => $this->getDescription($Locale),
            'unitPrice'            => $this->getUnitPrice()->value(),
            'nettoPriceNotRounded' => $this->nettoPriceNotRounded,
            'nettoSumNotRounded'   => $this->nettoSumNotRounded,
            'quantity'             => $this->getQuantity(),
            'customFields'         => $this->getCustomFieldsData(),
            'customData'           => $this->getCustomData(),
            'displayPrice'         => true
        ];

        // quantity unit
        $SysField = QUI\ERP\Products\Handler\Fields::getField(Fields::FIELD_UNIT);
        $Field    = $this->getField(Fields::FIELD_UNIT);

        if ($Field) {
            $value = $Field->getView()->getValue();

            if (empty($value)) {
                $value = [];
            }

            $value['title']          = $SysField->getTitleByValue($Field->getValue());
            $article['quantityUnit'] = $value;
        }

        if ($this->calculated) {
            if (isset($this->vatArray['vat'])) {
                $article['vat'] = $this->vatArray['vat'];
            }

            $article['calculated'] = [
                'price'                => $this->price,
                'basisPrice'           => $this->basisPrice,
                'nettoPriceNotRounded' => $this->nettoPriceNotRounded,
                'nettoSumNotRounded'   => $this->nettoSumNotRounded,
                'sum'                  => $this->sum,
                'nettoBasisPrice'      => $this->basisPrice,
                'nettoPrice'           => $this->nettoPrice,
                'nettoSum'             => $this->nettoSum,
                'vatArray'             => $this->vatArray,
                'isEuVat'              => $this->isEuVat,
                'isNetto'              => $this->isNetto
            ];
        }

        if ($this->existsAttribute('displayPrice')) {
            $article['displayPrice'] = (bool)$this->getAttribute('displayPrice');
        }

        $class = $this->getAttribute('class');

        if (\class_exists($class)) {
            $interfaces = \class_implements($class);

            if (isset($interfaces[ArticleInterface::class])) {
                return new $class($article);
            }
        }

        return new QUI\ERP\Accounting\Article($article);
    }

    /**
     * Return the custom fields for saving
     *
     * @return array
     */
    protected function getCustomFieldsData()
    {
        $fields       = $this->getCustomFields();
        $customFields = [];

        if (!\count($fields)) {
            return [];
        }

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            $attributes = $Field->getAttributes();

            if (isset($attributes['options'])) {
                unset($attributes['options']);
            }

            $customFields[$Field->getId()] = $attributes;
        }

        // price factors -> quiqqer/discount#7
        $priceFactors = $this->getPriceFactors()->toErpPriceFactorList()->toArray();

        foreach ($priceFactors as $factor) {
            if (!empty($factor['identifier'])) {
                $factor['custom_calc']['valueText'] = $factor['valueText'];
                $factor['custom_calc']['value']     = $factor['value'];

                $customFields[$factor['identifier']] = $factor;
            }
        }

        return $customFields;
    }

    /**
     * Return the custom fields for saving
     *
     * @return array
     */
    protected function getCustomData()
    {
        $data = $this->getAttribute('customData');

        if (\is_array($data)) {
            return $data;
        }

        return [];
    }
}
