<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Field;

use QUI;
use QUI\ERP\Products\Handler\Search;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class Field
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID );
 */
abstract class Field extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Field
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected $id;

    /**
     * @var bool
     */
    protected $system = false;

    /**
     * @var bool
     */
    protected $standard = false;

    /**
     * @var bool
     */
    protected $require = false;

    /**
     * @var bool
     */
    protected $unassigned = false;

    /**
     * @var bool
     */
    protected $ownField = false;

    /**
     * @var bool
     */
    protected $public = true;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Is this Field searchable?
     *
     * @var bool
     */
    protected $searchable = true;

    /**
     * Field-Name
     *
     * @var string
     */
    protected $name;

    /**
     * Field value
     *
     * @var mixed
     */
    protected $value = '';

    /**
     * Column type for database table (cache column)
     *
     * @var string
     */
    protected $columnType = 'LONGTEXT';

    /**
     * @var array
     */
    protected $searchTypes = array();

    /**
     * Model constructor.
     *
     * @param integer $fieldId
     * @param array $params - optional, field params (system, require, standard)
     */
    public function __construct($fieldId, $params = array())
    {
        $this->id = (int)$fieldId;

        if (defined('QUIQQER_BACKEND')) {
            $this->setAttribute('viewType', 'backend');
        }

        // field types
        if (isset($params['public'])
            && (is_bool($params['public']) || is_int($params['public']))
        ) {
            $this->public = $params['public'] ? true : false;
        }

        if (isset($params['system'])
            && (is_bool($params['system']) || is_int($params['system']))
        ) {
            $this->system = $params['system'] ? true : false;
        }

        if (isset($params['required'])
            && (is_bool($params['required']) || is_int($params['required']))
        ) {
            $this->require = $params['required'] ? true : false;
        }

        if (isset($params['standard'])
            && (is_bool($params['standard']) || is_int($params['standard']))
        ) {
            $this->standard = $params['standard'] ? true : false;
        }

        if (isset($params['options'])) {
            $this->setOptions($params['options']);
        }
    }

    /**
     * Return the view for the backend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    protected function getBackendView()
    {
        return new View($this->getAttributes());
    }

    /**
     * Return the view for the frontend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    protected function getFrontendView()
    {
        return new View($this->getAttributes());
    }

    /**
     * Create a unique field with the current field data
     *
     * @return UniqueField
     */
    public function createUniqueField()
    {
        return new UniqueField($this->getId(), $this->getAttributes());
    }

    /**
     * Return the name of the JavaScript Control for the field
     *
     * @return string
     */
    abstract public function getJavaScriptControl();

    /**
     * Return the view
     *
     * @return \QUI\ERP\Products\Field\View
     */
    public function getView()
    {
        switch ($this->getAttribute('viewType')) {
            case 'backend':
                return $this->getBackendView();
                break;

            default:
                return $this->getFrontendView();
        }
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
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Price    = new QUI\ERP\Products\Utils\Price(0, $Currency);

        return $Price;
    }

    /**
     * saves / update the field
     *
     * @todo value check
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('field.edit');

        $allowedAttributes = Fields::getChildAttributes();

        $data = array();

        foreach ($allowedAttributes as $attribute) {
            if ($this->getAttribute($attribute)) {
                $data[$attribute] = $this->getAttribute($attribute);
            } else {
                $data[$attribute] = '';
            }
        }

        // options json check
        $data['options'] = '';
        $options         = $this->getOptions();

        if (!empty($options)) {
            $data['options'] = json_encode($options);
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data,
            array('id' => $this->getId())
        );

        // set cache
        QUI\Cache\Manager::set(
            Fields::getFieldCacheName($this->getId()),
            $data
        );
    }

    /**
     * Delete the field
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('field.delete');

        if ($this->isSystem()) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exceptions.system.fields.cant.be.deleted'
            ));
        }

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            array('id' => $this->getId())
        );

        // delete the locale
        QUI\Translator::delete(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );

        QUI\Translator::delete(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.workingtitle'
        );

        // delete column
        QUI::getDataBase()->table()->deleteColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'F' . $this->getId()
        );

        // delete cache
        QUI\Cache\Manager::clear(
            Fields::getFieldCacheName($this->getId())
        );
    }

    /**
     * Delete the field
     *
     * @throws QUI\Exception
     */
    public function deleteSystemField()
    {
        QUI\Rights\Permission::checkPermission('field.delete');
        QUI\Rights\Permission::checkPermission('field.delete.systemfield');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            array('id' => $this->getId())
        );

        // delete the locale
        QUI\Translator::delete(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );

        QUI\Translator::delete(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.workingtitle'
        );


        // delete column
        QUI::getDataBase()->table()->deleteColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'F' . $this->getId()
        );


        // delete cache
        QUI\Cache\Manager::clear(
            Fields::getFieldCacheName($this->getId())
        );
    }

    /**
     * Is the field a public field
     * is the field visible at the product view
     *
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Set the public field status
     *
     * @param boolean $status
     */
    public function setPublicStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->public = $status;
    }

    /**
     * Is the field a own field from the product?
     *
     * @return boolean
     */
    public function isOwnField()
    {
        return $this->ownField;
    }

    /**
     * Set the own field status
     *
     * @param boolean $status
     */
    public function setOwnFieldStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->ownField = $status;
    }

    /**
     * Is the field unassigned?
     *
     * @return boolean
     */
    public function isUnassigned()
    {
        return $this->unassigned;
    }

    /**
     * Set the unassigned status
     *
     * @param boolean $status
     */
    public function setUnassignedStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->unassigned = $status;
    }

    /**
     * Is the field a custom field?
     *
     * @return boolean
     */
    public function isCustomField()
    {
        return $this instanceof QUI\ERP\Products\Field\CustomField;
    }

    /**
     * Set the field name
     *
     * @param string $name
     * @deprecated maybe? ... getTitle makes more sense
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the field value
     *
     * @param mixed $value
     * @throws QUI\Exception
     */
    public function setValue($value)
    {
        $this->validate($value);
        $this->value = $this->cleanup($value);
    }

    /**
     * @param array|string $options - field options
     */
    public function setOptions($options)
    {
        if (is_string($options)) {
            $options = json_decode($options, true);
        }

        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $this->setOption($key, $value);
            }
        }
    }

    /**
     * Set a field option
     *
     * @param string $option - option name
     * @param mixed $value - option value
     */
    public function setOption($option, $value)
    {
        if (is_string($option)) {
            $this->options[$option] = $value;
        }
    }

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param string|boolean|array|object $val - value of the attribute
     * @return Field
     */
    public function setAttribute($name, $val)
    {
        switch ($name) {
            case 'name':
            case 'type':
            case 'search_type':
            case 'prefix':
            case 'suffix':
            case 'priority':
            case 'standardField':
            case 'systemField':
            case 'requiredField':
            case 'publicField':
                $val = QUI\Utils\Security\Orthos::clear($val);
                break;

            default:
                return $this;
        }

        return parent::setAttribute($name, $val);
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
     * @return array|string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return the value of the option, if the option exists
     *
     * @param string $option - option name
     * @return mixed
     */
    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return false;
    }

    /**
     * Return the suffix
     *
     * @param QUI\Locale|bool $Locale
     * @return string|bool
     */
    public function getSuffix($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $suffix  = $this->getAttribute('suffix');
        $suffix  = json_decode($suffix, true);

        if (!is_array($suffix)) {
            return $this->getAttribute('suffix');
        }

        if (isset($suffix[$current])) {
            return $suffix[$current];
        }

        return $this->getAttribute('suffix');
    }

    /**
     * Return the prefix
     *
     * @param QUI\Locale|bool $Locale
     * @return string|bool
     */
    public function getPrefix($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $prefix  = $this->getAttribute('prefix');
        $prefix  = json_decode($prefix, true);

        if (!is_array($prefix)) {
            return $this->getAttribute('prefix');
        }

        if (isset($prefix[$current])) {
            return $prefix[$current];
        }

        return $this->getAttribute('prefix');
    }

    /**
     * Return the title of the field
     * The title are from the user and translated
     *
     * @param QUI\Locale|bool $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false)
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );
    }

    /**
     * Return the working title
     *
     * @param QUI\Locale|Boolean $Locale - optional
     * @return string
     */
    public function getWorkingTitle($Locale = false)
    {
        $var   = 'products.field.' . $this->getId() . '.workingtitle';
        $group = 'quiqqer/products';

        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        if ($Locale->exists($group, $var)) {
            return $Locale->get($group, $var);
        }

        return $this->getTitle();
    }

    /**
     * Return the field type
     *
     * @return mixed|string
     */
    public function getType()
    {
        $type = parent::getType();
        $type = str_replace('QUI\ERP\Products\Field\Types\\', '', $type);

        return $type;
    }

    /**
     * Return column type for database column (cache table)
     *
     */
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * Get all available search types for this field
     *
     * @return array
     */
    public function getSearchTypes()
    {
        if (!$this->isSearchable()) {
            return array();
        }

        return Search::getSearchTypes();
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes                 = parent::getAttributes();
        $attributes['id']           = $this->getId();
        $attributes['title']        = $this->getTitle();
        $attributes['workingtitle'] = $this->getWorkingTitle();
        $attributes['type']         = $this->getType();
        $attributes['options']      = $this->getOptions();
        $attributes['jsControl']    = $this->getJavaScriptControl();
        $attributes['custom']       = $this->isCustomField();
        $attributes['unassigned']   = $this->isUnassigned();
        $attributes['isRequired']   = $this->isRequired();
        $attributes['isStandard']   = $this->isStandard();
        $attributes['isSystem']     = $this->isSystem();
        $attributes['isPublic']     = $this->isPublic();
        $attributes['searchable']   = $this->isSearchable();
        $attributes['ownField']     = $this->isOwnField();

        return $attributes;
    }

    /**
     * Return the field attributes for a product
     *
     * @return array
     */
    public function toProductArray()
    {
        $attributes['id'] = $this->getId();

        $attributes['value']      = $this->getValue();
        $attributes['unassigned'] = $this->isUnassigned();
        $attributes['ownField']   = $this->isOwnField();
        $attributes['isPublic']   = $this->isPublic();

        return $attributes;
    }

    /**
     * Is the value of this value an empty value?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->value);
    }

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * @return bool
     */
    public function isStandard()
    {
        return $this->standard;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->require;
    }

    /**
     * Is this field allowed as a searchable field?
     *
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }
}
