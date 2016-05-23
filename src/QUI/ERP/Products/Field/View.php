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
class View extends UniqueField
{
    /**
     * View constructor.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $fieldId = false;

        if ($params['id']) {
            $fieldId = $params['id'];
        }

        parent::__construct($fieldId, $params);
    }

    /**
     * Return the html
     *
     * @return string
     */
    public function create()
    {
        try {
            QUI\Permissions\Permission::checkPermission(
                "permission.products.fields.field{$this->getId()}.view"
            );
        } catch (QUI\Exception $Exception) {
            return '';
        }

        return '<div class="quiqqer-product-field">
            <div class="quiqqer-product-field-title">' . $this->getTitle() . '</div>
            <div class="quiqqer-product-field-value">' . $this->getValue() . '</div>
        </div>';
    }
}
