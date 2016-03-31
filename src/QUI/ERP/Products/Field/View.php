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
     * View constructor
     *
     * @param array $data
     */
    public function __construct($data = array())
    {
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
}
