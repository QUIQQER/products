<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\VatFrontendView
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

use function htmlspecialchars;

/**
 * Class DateFrontendView
 *
 * @package QUI\ERP\Products\Field\Types
 */
class VatFrontendView extends View
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

        $value = (int)$this->getValue();
        $taxTitle = '---';

        if ($value >= 0) {
            try {
                $Area = QUI\ERP\Utils\User::getUserArea(QUI::getUserBySession());
                $TaxType = QUI\ERP\Tax\Handler::getInstance()->getTaxType($value);
                $Tax = QUI\ERP\Tax\Utils::getTaxEntry($TaxType, $Area);

                $taxTitle = QUI::getLocale()->get('quiqqer/products', 'fieldtype.Tax.frontend.text', [
                    'tax' => $Tax->getValue(),
                    'title' => $TaxType->getTitle()
                ]);
            } catch (QUI\Exception) {
            }
        }

        $title = htmlspecialchars($this->getTitle());
        $title = htmlspecialchars($title);

        return "<div class=\"quiqqer-product-field\">
            <div class=\"quiqqer-product-field-title\">$title</div>
            <div class=\"quiqqer-product-field-value\">$taxTitle</div>
        </div>";
    }
}
