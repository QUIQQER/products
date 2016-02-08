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
    }

    /**
     * Return the view for the backend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    abstract protected function getBackendView();

    /**
     * Return the view for the frontend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    abstract protected function getFrontendView();

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

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data,
            array('id' => $this->getId())
        );
    }

    /**
     * Delete the field
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('field.delete');

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
            array('F' . $this->getId())
        );
    }

    /**
     * Set the field name
     *
     * @param string $name
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
     * Return the title / name of the category
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
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        $type = $this->getType();
        $type = str_replace('QUI\ERP\Products\Field\Types\\', '', $type);

        $attributes          = parent::getAttributes();
        $attributes['id']    = $this->getId();
        $attributes['title'] = $this->getTitle();
        $attributes['type']  = $type;

        return $attributes;
    }

    /**
     * @return array
     */
    public function toProductArray()
    {
        return array(
            'id' => (string)$this->getId(),
            'value' => $this->getValue()
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
