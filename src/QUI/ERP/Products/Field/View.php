<?php

/**
 * This file contains QUI\ERP\Products\Field\View
 */

namespace QUI\ERP\Products\Field;

use QUI;
use QUI\Interfaces\Users\User;

use function htmlspecialchars;
use function is_numeric;
use function is_string;

/**
 * Class View
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
    public function create(): string
    {
        if (!$this->hasViewPermission()) {
            return '';
        }

        $value = $this->getValue();

        if (!is_numeric($value) && !is_string($value)) {
            $value = '';
        }

        $value = htmlspecialchars($value);
        $title = htmlspecialchars($this->getTitle());

        return '<div class="quiqqer-product-field">
            <div class="quiqqer-product-field-title">' . $title . '</div>
            <div class="quiqqer-product-field-value">' . $value . '</div>
        </div>';
    }

    /**
     * Has the user view permissions
     *
     * @param User|null $User
     * @return bool
     */
    public function hasViewPermission(null | QUI\Interfaces\Users\User $User = null): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        try {
            QUI\Permissions\Permission::checkPermission(
                "permission.products.fields.field{$this->getId()}.view",
                $User
            );

            return true;
        } catch (QUI\Exception) {
        }

        return false;
    }
}
