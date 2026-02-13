<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\DateFrontendView
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

use function htmlspecialchars;

/**
 * Class DateFrontendView
 */
class DateFrontendView extends View
{
    /**
     * Render the view, return the html
     *
     * @return string
     */
    public function create(): string
    {
        if (!$this->hasViewPermission()) {
            return '';
        }

        $title = htmlspecialchars($this->getTitle());
        $title = htmlspecialchars($title);
        $date = QUI::getLocale()->formatDate($this->getValue());

        return "<div class=\"quiqqer-product-field\">
            <div class=\"quiqqer-product-field-title\">$title</div>
            <div class=\"quiqqer-product-field-value\">$date</div>
        </div>";
    }
}
