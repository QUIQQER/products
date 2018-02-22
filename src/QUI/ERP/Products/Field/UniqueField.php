<?php

/**
 * This file contains QUI\ERP\Products\Field\UniqueField
 */

namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class UniqueField
 * This field is a field for the view, unique fields are not editable
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID )->createUniqueField();
 */
class UniqueField implements QUI\ERP\Products\Interfaces\UniqueFieldInterface
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected $id;

    /**
     * Field name
     *
     * @var string
     */
    protected $name;

    /**
     * Field title
     * @var string|array
     */
    protected $title;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var string
     */
    protected $suffix = '';

    /**
     * @var integer
     */
    protected $priority;

    /**
     * Field value
     *
     * @var mixed
     */
    protected $value = '';

    /**
     * @var string
     */
    protected $type;

    /**
     * is customfield?
     * Custom field is a field, which can be filled by the visitors
     *
     * @var boolean
     */
    protected $custom;

    /**
     * custom field calculation data
     * @var array
     */
    protected $custom_calc;

    /**
     * search cache value
     *
     * @var string
     */
    protected $searchvalue;

    /**
     * is field public
     * field which is visble by the visitors, too
     *
     * @var boolean
     */
    protected $isPublic = false;

    /**
     * Field from the system, like price
     * @var bool
     */
    protected $isSystem = false;

    /**
     * @var boolean
     */
    protected $isStandard = false;

    /**
     * @var boolean
     */
    protected $isRequired = false;

    /**
     * @var bool
     */
    protected $showInDetails = false;

    /**
     * a field in the product, but not in any category from the product
     *
     * @var boolean
     */
    protected $unassigned = false;

    /**
     * Is the field a product own field
     * @var boolean
     */
    protected $ownField = false;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Is the field in the frontend currently changeable
     *
     * @var bool
     */
    protected $changeable = true;

    /**
     * Model constructor.
     *
     * @param integer $fieldId
     * @param array $params - optional, field params (system, require, standard)
     */
    public function __construct($fieldId, $params = array())
    {
        $this->id = (int)$fieldId;

        $attributes = array(
            'title',
            'type',
            'prefix',
            'suffix',
            'priority',
            'options',
            'isRequired',
            'isStandard',
            'isSystem',
            'isPublic',
            'custom',
            'custom_calc',
            'unassigned',
            'value',
            'ownField',
            'showInDetails',
            'searchvalue',
            'changeable'
        );

        if (!isset($params['isPublic'])) {
            $this->isPublic = true;
        }

        if (!isset($params['showInDetails'])) {
            $this->showInDetails = true;
        }

        foreach ($attributes as $attribute) {
            if (!isset($params[$attribute])) {
                continue;
            }

            if (property_exists($this, $attribute)) {
                $this->$attribute = $params[$attribute];
            }
        }
    }

    /**
     * Return the view
     *
     * @return View
     */
    public function getView()
    {
        $type = $this->getType();

        if (defined('QUIQQER_BACKEND')) {
            $viewClass = 'QUI\ERP\Products\Field\Types\\'.$type.'BackendView';
        } else {
            $viewClass = 'QUI\ERP\Products\Field\Types\\'.$type.'FrontendView';
        }

        if (class_exists($viewClass)) {
            return new $viewClass($this->getAttributes());
        }

        return new View($this->getAttributes());
    }

    /**
     * Return Field-ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Change the price of the product
     * Returns the price object
     *
     * @return QUI\ERP\Money\Price
     */
    public function getPrice()
    {
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Price    = new QUI\ERP\Money\Price(0, $Currency);

        return $Price;
    }

    /**
     * Return the type of the parent field
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Is the field unassigned?
     *
     * @return bool
     */
    public function isUnassigned()
    {
        return $this->unassigned;
    }

    /**
     * @return bool
     */
    public function isCustomField()
    {
        return $this->custom ? true : false;
    }

    /**
     * Is the field currently changeable
     * This flag is needed for the frontend view
     *
     * @return bool
     */
    public function isChangeable()
    {
        return $this->changeable;
    }

    /**
     * Return the field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the value in dependence of a locale (language)
     *
     * @param bool $Locale
     * @return array|string
     */
    public function getValueByLocale($Locale = false)
    {
        return $this->getValue();
    }

    /**
     * @return array|string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param QUI\Locale $Locale
     * @return string
     */
    public function getSearchCacheValue($Locale = null)
    {
        return $this->searchvalue;
    }

    /**
     * Return the title of the field
     * The title are from the user and translated
     *
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false)
    {
        if (!$Locale) {
            return QUI::getLocale()->get(
                'quiqqer/products',
                'products.field.'.$this->getId().'.title'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.field.'.$this->getId().'.title'
        );
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return array(
            'id'         => $this->getId(),
            'title'      => $this->getTitle(),
            'type'       => $this->getType(),
            'options'    => $this->getOptions(),
            'isRequired' => $this->isRequired(),
            'isStandard' => $this->isStandard(),
            'isSystem'   => $this->isSystem(),
            'isPublic'   => $this->isPublic(),

            'prefix'        => $this->prefix,
            'suffix'        => $this->suffix,
            'priority'      => $this->priority,
            'custom'        => $this->isCustomField(),
            'custom_calc'   => $this->custom_calc,
            'unassigned'    => $this->isUnassigned(),
            'value'         => $this->getValue(),
            'showInDetails' => $this->showInDetails()
        );
    }

    /**
     * Is the field currently changeable
     * This flag is needed for the frontend view
     *
     * @param bool $status
     */
    public function setChangeableStatus($status)
    {
        $this->changeable = (bool)$status;
    }

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem()
    {
        return $this->isSystem;
    }

    /**
     * @return boolean
     */
    public function isStandard()
    {
        return $this->isStandard;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * @return boolean
     */
    public function isOwnField()
    {
        return $this->ownField;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->isPublic;
    }

    /**
     * Show the fields in the details?
     *
     * @return boolean
     */
    public function showInDetails()
    {
        return $this->showInDetails;
    }
}
