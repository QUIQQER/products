<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Url
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;

use function filter_var;

/**
 * Class FloatType
 *
 * @package QUI\ERP\Products\Field
 */
class Url extends QUI\ERP\Products\Field\Field
{
    /**
     * @var string
     */
    protected string $columnType = 'TEXT';

    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * @return View
     */
    public function getBackendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * Return the FrontendView
     *
     * @return UrlFrontendView
     */
    public function getFrontendView(): UrlFrontendView
    {
        return new UrlFrontendView($this->getFieldDataForView());
    }


    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Url';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws Exception
     */
    public function validate(mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
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
    public function cleanup(mixed $value): mixed
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $value;
    }
}
