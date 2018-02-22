<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ProductAttributeListFrontendView
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class ProductAttributeList - Backend VIEW (eq -> invoice)
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
class ProductAttributeListBackendView extends QUI\ERP\Products\Field\View
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

        $current = QUI::getLocale()->getCurrent();

        $id      = $this->getId();
        $value   = $this->getValue();
        $options = $this->getOptions();

        $name    = 'field-'.$id;
        $entries = array();

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        if (!is_string($value) && !is_numeric($value)) {
            $value = '';
        }

        $value = htmlspecialchars($value);

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }


        $Engine->assign(array(
            'this'  => $this,
            'id'    => $id,
            'title' => $this->getTitle(),
            'name'  => $name,
            'value' => $value
        ));

        // options
        $currentLC = strtolower($current).'_'.strtoupper($current);

        $option = $entries[$value];
        $title  = $option['title'];
        $text   = '';

        if (is_string($title)) {
            $text = $title;
        } elseif (isset($title[$current])) {
            $text = $title[$current];
        } elseif (isset($title[$currentLC])) {
            $text = $title[$currentLC];
        }

        $Engine->assign('valueText', $text);

        return $Engine->fetch(dirname(__FILE__).'/ProductAttributeListBackendView.html');
    }
}
