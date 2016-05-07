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
        $id       = $this->getId();
        $value    = $this->getValue();
        $options  = $this->getOptions();
        $current  = QUI::getLocale()->getCurrent();
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $name     = 'field-' . $id;
        $entries  = array();

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        $display_discounts = false;

        if (isset($options['display_discounts'])) {
            $display_discounts = $options['display_discounts'];
        }

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

        $hasDefault = function () use ($entries) {
            foreach ($entries as $key => $option) {
                if (isset($option['selected']) && $option['selected']) {
                    return true;
                }
            }
            return false;
        };

        if ($value === '' && !$hasDefault()) {
            $html .= '<option value="">Bitte auswählen</option>';
        }

        foreach ($entries as $key => $option) {
            $text     = '';
            $title    = $option['title'];
            $selected = '';

            if (isset($option['selected']) && $option['selected']) {
                $selected = 'selected="selected" ';
            }

            if (isset($title[$current])) {
                $text = $title[$current];
            }

            if ($display_discounts) {
                switch ($option['type']) {
                    case 'percent': // fallback fix
                    case QUI\ERP\Products\Utils\Calc::CALCULATION_PERCENTAGE:
                        $discount = $option['sum'] . '%';
                        break;

                    case QUI\ERP\Products\Utils\Calc::CALCULATION_COMPLEMENT:
                    default:
                        $discount = $Currency->format($option['sum']);
                        break;
                }

                $text .= ' (+' . $discount . ')';
            }

            $html .= '<option ' . $selected . 'value="' . $key . '">' . $text . '</option>';
        }

        $html .= '</select></div>';
        $html .= '</div>';

        return $html;
    }
}
