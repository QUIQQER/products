<?php

/**
 * This file contains QUI\ERP\Products\Field\Modell
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
abstract class Modell extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Field
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected $id;

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
     * Modell constructor.
     *
     * @param integer $fieldId
     */
    public function __construct($fieldId)
    {
        $this->id = (int)$fieldId;
    }

    /**
     * @return Controller
     */
    protected function getController()
    {
        return new Controller($this);
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
     * saves the field
     */
    public function save()
    {
        $this->getController()->save();
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
        $this->checkValue($value);
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
     * @return array
     */
    public function toProductArray()
    {
        return array(
            'id' => (string)$this->getId(),
            'value' => $this->getValue()
        );
    }
}
