<?php

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Utils\PriceFactor;
use QUI\Projects\Media\Utils as MediaUtils;

/**
 * Class UniqueProduct
 *
 * @event onQuiqqerProductsPriceFactorsInit [QUI\ERP\Products\Utils\PriceFactors, QUI\ERP\Products\Interfaces\Product]
 */
class UniqueProduct extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * is the product list calculated?
     * @var bool
     */
    protected $calulated = false;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer|float
     */
    protected $quantity = 1;

    /**
     * @var array
     */
    protected $categories = array();

    /**
     * @var null|QUI\ERP\Products\Category\Category
     */
    protected $Category = null;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * Price factors
     *
     * @var QUI\ERP\Products\Utils\PriceFactors
     */
    protected $PriceFactors;

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * calculated price
     * @var float|int
     */
    protected $price;

    /**
     * calculated sum
     * @var float|int
     */
    protected $sum;

    /**
     * calculated nettosum
     * @var float|int
     */
    protected $nettoSum;

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array
     */
    protected $vatArray = array();

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
    protected $factors = array();

    /**
     * UniqueProduct constructor.
     *
     * @param integer $pid - Product ID
     * @param $attributes - attributes
     */
    public function __construct($pid, $attributes = array())
    {
        $this->id         = $pid;
        $this->attributes = $attributes;

        // fields
        $this->parseFieldsFromAttributes($attributes);
        $this->parseCategoriesFromAttributes($attributes);

        // generate the price factors
        $fields = $this->getFields();

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            if (!$Field->isCustomField()) {
                continue;
            }

            $attributes = $Field->getAttributes();

            $Factor = new PriceFactor($attributes['custom_calc']);
            $Factor->setTitle($Field->getTitle());

            $this->PriceFactors->add($Factor);
        }

        QUI::getEvents()->fireEvent(
            'quiqqerProductsPriceFactorsInit',
            array($this->PriceFactors, $this)
        );
    }

    /**
     * Parse the field data
     *
     * @param array $attributes - product attributes
     */
    protected function parseFieldsFromAttributes($attributes = array())
    {
        if (!isset($attributes['fields'])) {
            return;
        }

        $fields = $attributes['fields'];

        foreach ($fields as $field) {
            $this->fields[] = new UniqueField($field['id'], $field);
        }
    }

    /**
     * Parse the category data
     *
     * @param array $attributes
     */
    protected function parseCategoriesFromAttributes($attributes = array())
    {
        if (!isset($attributes['categories'])) {
            return;
        }

        $list       = array();
        $categories = explode(',', $attributes['categories']);

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
    protected function parseCategoryFromAttributes($attributes = array())
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
        return md5(serialize($this->getAttributes()));
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
     * Calculates
     *
     * @param QUI\ERP\Products\Utils\Calc|null $Calc - optional, calculation object
     * @return UniqueProduct
     */
    public function calc($Calc = null)
    {
        if ($this->calulated) {
            return $this;
        }

        $self = $this;

        if (!$Calc) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance();
        }

        $Calc->getProductPrice($this, function ($data) use ($self) {
            $self->price    = $data['price'];
            $self->sum      = $data['sum'];
            $self->nettoSum = $data['nettoSum'];
            $self->vatArray = $data['vatArray'];
            $self->isEuVat  = $data['isEuVat'];
            $self->isNetto  = $data['isNetto'];
            $self->factors  = $data['factors'];

            $self->calulated = true;
        });

        return $this;
    }

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

        if (is_string($values)) {
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

        $current = $Locale->getCurrent();
        $Title   = $this->getField(Fields::FIELD_SHORT_DESC);
        $values  = $Title->getValue();

        if (is_string($values)) {
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

        if (is_string($values)) {
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
                return $Folder->firstChild();
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

        throw new QUI\ERP\Products\Product\Exception(array(
            'quiqqer/products',
            'exception.product.no.image',
            array(
                'productId' => $this->getId()
            )
        ));
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
        if (QUI::isFrontend()) {
            return $this->getPublicFields();
        }

        return $this->fields;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getFieldsByType($type)
    {
        $fields = $this->getFields();
        $result = array();

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
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        $this->calc();

        return new QUI\ERP\Products\Utils\Price(
            $this->price,
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
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
        $result = array();

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
        $result = array();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($this->fields as $Field) {
            if ($Field->isPublic()) {
                $result[$Field->getId()] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return the main catgory
     *
     * @return QUI\ERP\Products\Handler\Categories|null
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
        if (!is_numeric($quantity)) {
            return;
        }

        $this->quantity = $quantity;
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
        $attributes             = parent::getAttributes();
        $attributes['quantity'] = $this->getQuantity();
        $attributes['id']       = $this->getId();
        $attributes['fields']   = $this->getFields();

        $attributes['calculated_price']    = $this->price;
        $attributes['calculated_sum']      = $this->sum;
        $attributes['calculated_nettoSum'] = $this->nettoSum;
        $attributes['calculated_isEuVat']  = $this->isEuVat;
        $attributes['calculated_isNetto']  = $this->isNetto;
        $attributes['calculated_vatArray'] = $this->vatArray;
        $attributes['calculated_factors']  = $this->factors;

        if (isset($attributes['fieldData'])) {
            unset($attributes['fieldData']);
        }

        return $attributes;
    }

    /**
     * Alias for getAttributes()
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }
}
