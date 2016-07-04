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
        if (!$this->hasViewPermission()) {
            return '';
        }

        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $current  = QUI::getLocale()->getCurrent();

        $id      = $this->getId();
        $value   = $this->getValue();
        $options = $this->getOptions();

        $name    = 'field-' . $id;
        $entries = array();

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        $requiredField    = '';
        $displayDiscounts = false;

        if (isset($options['display_discounts'])) {
            $displayDiscounts = $options['display_discounts'];
        }

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $displayDiscounts = false;
        }

        if ($this->isRequired()) {
            $requiredField = ' required="required"';
        }

        if (!is_string($value)) {
            $value = '';
        }

        $value = htmlspecialchars($value);

        // create html
        $html = '<div class="quiqqer-product-field">';
        $html .= '<div class="quiqqer-product-field-title">' . $this->getTitle() . '</div>';
        $html .= '<div class="quiqqer-product-field-value">';
        $html .= "<select name=\"{$name}\" value=\"{$value}\" {$requiredField}
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
            $html .= '<option value="">' .
                     QUI::getLocale()->get('quiqqer/products', 'fieldtype.ProductAttributeList.select.emptyvalue') .
                     '</option>';
        }

        foreach ($entries as $key => $option) {
            $title = $option['title'];

            $text      = '';
            $selected  = '';
            $userinput = '';

            if (isset($option['selected']) && $option['selected']) {
                $selected = 'selected="selected" ';
            }

            if (isset($title[$current])) {
                $text = $title[$current];
            }

            if (isset($option['userinput']) && $option['userinput']) {
                $userinput = ' data-userinput="1"';
            }

            if (!isset($option['sum']) || !$option['sum']) {
                $option['sum'] = 0;
            }

            if ($displayDiscounts && $option['sum'] != 0) {
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

            $html .= '<option ' . $selected . $userinput . 'value="' . $key . '">' . $text . '</option>';
        }

        $html .= '</select></div>';
        $html .= '</div>';

        return $html;
    }
}
