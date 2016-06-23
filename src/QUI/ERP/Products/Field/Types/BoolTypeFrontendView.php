<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\BoolTypeFrontendView
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use Hklused\Machines\Purchase\Search;

/**
 * Class BoolType
 * Frontend View for the BoolType Field
 *
 * @package QUI\ERP\Products\Field
 */
class BoolTypeFrontendView extends QUI\ERP\Products\Field\View
{
    /**
     * Render the view, return the html
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

        $title = htmlspecialchars($this->getTitle());
        $title = htmlspecialchars($title);

        if ($this->getValue()) {
            $html = '<span class="fa fa-check"></span>';
        } else {
            $html = '<span class="fa fa-remove"></span>';
        }

        return "<div class=\"quiqqer-product-field-title\">{$title}</div>
            <div class=\"quiqqer-product-field-value\">{$html}</div>";
    }
}