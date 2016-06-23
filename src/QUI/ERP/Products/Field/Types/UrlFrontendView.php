<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Url
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use Hklused\Machines\Purchase\Search;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class UrlFrontendView extends QUI\ERP\Products\Field\View
{
    /**
     * Render the view, return the html
     *
     * @return string
     */
    public function create()
    {
        $title = $this->getTitle();
        $title = htmlspecialchars($title);

        $link  = '';
        $value = $this->getValue();

        if (!empty($value)) {
            $link = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        return "<div class=\"quiqqer-product-field-title\">{$title}</div>
            <div class=\"quiqqer-product-field-value\">{$link}</div>";
    }
}
