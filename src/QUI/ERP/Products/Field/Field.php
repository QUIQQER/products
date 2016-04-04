<?php

/**
 * This file contains QUI\ERP\Products\Field\Model
 */
namespace QUI\ERP\Products\Field;

use QUI;

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
     * @var array
     */
    protected $options = array();

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
        return new View(array(
            'id' => $this->getId(),
            'value' => $this->getValue(),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority'),
            'options' => $this->getOptions()
        ));
    }

    /**
     * Return the view for the frontend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    protected function getFrontendView()
    {
        return new View(array(
            'id' => $this->getId(),
            'value' => $this->getValue(),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority'),
            'options' => $this->getOptions()
        ));
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
     * saves / update the field
     *
     * @todo value check
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('field.edit');

        $allowedAttributes = QUI\ERP\Products\Handler\Fields::getChildAttributes();

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
        $options         = json_encode($this->getOptions());

        if ($options) {
            $data['options'] = json_encode($options);
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data,
            array('id' => $this->getId())
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

        // delete column
        QUI::getDataBase()->table()->deleteColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            'F' . $this->getId()
        );
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
     * Set the unassigned status
     *
     * @param bool $status
     */
    public function setUnassignedStatus($status)
    {
        if (is_bool($status)) {
            $this->unassigned = $status;
        }
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
        $this->value = $value;
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
            $this->options = $options;
        }
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
                'products.field.' . $this->getId() . '.title'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );
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
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes            = parent::getAttributes();
        $attributes['id']      = $this->getId();
        $attributes['title']   = $this->getTitle();
        $attributes['type']    = $this->getType();
        $attributes['options'] = $this->getOptions();

        $attributes['isRequired'] = $this->isRequired();
        $attributes['isStandard'] = $this->isStandard();
        $attributes['isSystem']   = $this->isSystem();
        $attributes['jsControl']  = $this->getJavaScriptControl();

        return $attributes;
    }

    /**
     * @return array
     */
    public function toProductArray()
    {
        return array(
            'id' => (string)$this->getId(),
            'value' => $this->getValue(),
            'type' => $this->getType(),
            'unassigned' => $this->isUnassigned(),
            'options' => $this->getOptions()
        );
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
}
