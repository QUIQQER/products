<?php

/**
 * This file contains QUI\ERP\Products\Field\View
 */
namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class View
 *
 * @package QUI\ERP\Products\Field
 */
class View
{
    /**
     * @var string|array|mixed
     */
    protected $value = '';

    /**
     * @var string|int
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $title = '';

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
    protected $priority = 0;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * View constructor
     *
     * @param array $data
     */
    public function __construct($data = array())
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        if (isset($data['value'])) {
            $this->value = $data['value'];
        }

        if (isset($data['title'])) {
            $this->title = $data['title'];
        }

        if (isset($data['prefix'])) {
            $this->prefix = $data['prefix'];
        }

        if (isset($data['suffix'])) {
            $this->suffix = $data['suffix'];
        }

        if (isset($data['priority'])) {
            $this->priority = (int)$data['priority'];
        }

        if (isset($data['options']) && is_array($data['options'])) {
            $this->options = $data['options'];
        }
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return the html
     *
     * @return string
     */
    public function create()
    {
        return '<div class="quiqqer-product-field">
            <div class="quiqqer-product-field-title">' . $this->getTitle() . '</div>
            <div class="quiqqer-product-field-value">' . $this->getValue() . '</div>
        </div>';
    }
}
