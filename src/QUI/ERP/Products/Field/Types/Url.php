<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Url
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class Url extends QUI\ERP\Products\Field\Field
{
    /**
     * @var string
     */
    protected $columnType = 'TEXT';

    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * Return the FrontendView
     *
     * @return UrlFrontendView
     */
    public function getFrontendView()
    {
        return new UrlFrontendView($this->getFieldDataForView());
    }


    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Url';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        if (\filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType'  => $this->getType()
                ]
            ]);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup($value)
    {
        if (\filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $value;
    }
}
