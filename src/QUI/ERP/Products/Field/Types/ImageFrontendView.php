<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ImageFrontendView
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class ImageFrontendView
 * Frontend View for the Image Field
 *
 * @package QUI\ERP\Products\Field
 */
class ImageFrontendView extends QUI\ERP\Products\Field\View
{
    /**
     * Render the view, return the html
     *
     * @return string
     */
    public function create()
    {
        if (!$this->hasViewPermission()) {
            return '';
        }

        $title = $this->getTitle();
        $title = htmlspecialchars($title);

        $link  = '';
        $value = $this->getValue();

        try {
            $Image = QUI\Projects\Media\Utils::getImageByUrl($value);
            $url   = htmlspecialchars($Image->getSizeCacheUrl());
            $text  = htmlspecialchars($Image->getAttribute('title'));

            $link = "<a href=\"{$url}\" 
                        target='\"_blank\"' 
                        data-zoom=\"1\" 
                        data-src=\"{$url}\"
                        alt=\"{$text}\"
                     >
                            {$text}
                     </a>";
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addNotice($Exception->getMessage());
        }

        return "<div class=\"quiqqer-product-field\">
            <div class=\"quiqqer-product-field-title\">{$title}</div>
            <div class=\"quiqqer-product-field-value\">{$link}</div>
        </div>";
    }
}
