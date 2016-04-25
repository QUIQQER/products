<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ProductAttributeListFrontendView
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class ProductAttributeList - Frontend VIEW
 *
 * Beschreibung:
 * Dies ist die Auswahlliste
 *
 * Auswahlliste ist ein Feld welches dem Besucher verschiedenen Auswahleigenschaften zur Verfügung stellt.
 * Eine Auswahl kann den Preis des Produktes verändern
 *
 * Beispiel:
 * Oberfläche
 * -> Messing poliert lackiert (MP lackiert)
 * -> Messing poliert ohne Lack (MP ohne Lack)
 * -> Messing matt mit Lack (MM mit Lack)(nach Kundenspezifikation¹) +10%
 *
 * @package QUI\ERP\Products\Field\Types
 */
class ProductAttributeListFrontendView extends QUI\ERP\Products\Field\View
{
    /**
     * Render the view, return the html
     *
     * @return string
     */
    public function create()
    {
        $id      = $this->getId();
        $name    = 'field-' . $id;
        $value   = $this->getValue();
        $options = $this->getOptions();
        $current = QUI::getLocale()->getCurrent();

        if (!is_string($value)) {
            $value = '';
        }

        $value = htmlspecialchars($value);

        $html = '<div class="quiqqer-product-field">';
        $html .= '<div class="quiqqer-product-field-title">' . $this->getTitle() . '</div>';
        $html .= '<div class="quiqqer-product-field-value">';
        $html .= "<select name=\"{$name}\" value=\"{$value}\"
                    data-field=\"{$id}\"
                    data-qui=\"package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList\"
                    disabled=\"disabled\">";

        if ($value === '') {
            $html .= '<option value=""></option>';
        }

        $entries = array();

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        foreach ($entries as $key => $option) {
            $text  = '';
            $title = $option['title'];

            if (isset($title[$current])) {
                $text = $title[$current];
            }

            $html .= '<option value="' . $key . '">' . $text . '</option>';
        }

        $html .= '</select></div>';
        $html .= '</div>';

        return $html;
    }
}
