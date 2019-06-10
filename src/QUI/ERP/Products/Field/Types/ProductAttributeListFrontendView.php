<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ProductAttributeListFrontendView
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Accounting\Calc as ErpCalc;

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

        if ($this->isChangeable() === false) {
            return $this->notChangeableDisplay();
        }

        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $current  = QUI::getLocale()->getCurrent();

        $id      = $this->getId();
        $value   = $this->getValue();
        $options = $this->getOptions();

        $name    = 'field-'.$id;
        $entries = [];

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

        if (!\is_string($value) && !\is_numeric($value)) {
            $value = '';
        } elseif (\is_numeric($value)) {
            $value = (int)$value;
        }

        $value = \htmlspecialchars($value);

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }

        $Engine->assign([
            'this'          => $this,
            'id'            => $id,
            'title'         => $this->getTitle(),
            'name'          => $name,
            'value'         => $value,
            'requiredField' => $requiredField
        ]);

        // options
        $options = [];
//
//        $hasDefault = function () use ($entries) {
//            foreach ($entries as $key => $option) {
//                if (isset($option['selected']) && $option['selected']) {
//                    return true;
//                }
//            }
//
//            return false;
//        };

//        if ($value === '' && !$hasDefault()) {
//            $options[] = [
//                'value'    => '',
//                'text'     => QUI::getLocale()->get(
//                    'quiqqer/products',
//                    'fieldtype.ProductAttributeList.select.emptyvalue'
//                ),
//                'selected' => '',
//                'data'     => ''
//            ];
//        }

        $currentLC = \strtolower($current).'_'.\strtoupper($current);
        $Calc      = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());

        foreach ($entries as $key => $option) {
            $title = $option['title'];

            $text      = '';
            $selected  = '';
            $disabled  = '';
            $userInput = '';

            if (isset($option['selected']) && $option['selected']
                || (int)$value === $key && $value !== ''
            ) {
                $selected = 'selected="selected" ';
            }

            if (isset($option['disabled']) && $option['disabled'] || $value === $key) {
                $disabled = 'disabled="disabled" ';
                $selected = '';
            }

            if (\is_string($title)) {
                $text = $title;
            } elseif (isset($title[$current])) {
                $text = $title[$current];
            } elseif (isset($title[$currentLC])) {
                $text = $title[$currentLC];
            }

            if (isset($option['userinput']) && $option['userinput']) {
                $userInput = ' data-userinput="1"';
            }

            if (!isset($option['sum']) || !$option['sum']) {
                $option['sum'] = 0;
            }

            if ($displayDiscounts && $option['sum'] != 0) {
                switch ($option['type']) {
                    case 'percent': // fallback fix
                    case ErpCalc::CALCULATION_PERCENTAGE:
                        $discount = $option['sum'].'%';
                        break;

                    case ErpCalc::CALCULATION_COMPLEMENT:
                    default:
                        $discount = $Currency->format(
                            $Calc->getPrice($option['sum'])
                        );
                        break;
                }

                $text .= ' (+'.$discount.')';
            }

            $options[] = [
                'selected' => $selected,
                'disabled' => $disabled,
                'value'    => \htmlspecialchars($key),
                'text'     => \htmlspecialchars($text),
                'data'     => $userInput
            ];
        }


        $Engine->assign('options', $options);

        return $Engine->fetch(\dirname(__FILE__).'/ProductAttributeListFrontendView.html');
    }

    /**
     * @return string
     */
    protected function notChangeableDisplay()
    {
        $current = QUI::getLocale()->getCurrent();

        $id      = $this->getId();
        $value   = $this->getValue();
        $options = $this->getOptions();

        $name    = 'field-'.$id;
        $entries = [];

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        if (!\is_string($value) && !\is_numeric($value)) {
            $value = '';
        }

        $value = \htmlspecialchars($value);

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }


        $Engine->assign([
            'this'  => $this,
            'id'    => $id,
            'title' => $this->getTitle(),
            'name'  => $name,
            'value' => $value
        ]);

        // options
        $currentLC = \strtolower($current).'_'.\strtoupper($current);

        $option = $entries[$value];
        $title  = $option['title'];
        $text   = '';

        if (\is_string($title)) {
            $text = $title;
        } elseif (isset($title[$current])) {
            $text = $title[$current];
        } elseif (isset($title[$currentLC])) {
            $text = $title[$currentLC];
        }

        $Engine->assign('valueText', $text);

        return $Engine->fetch(\dirname(__FILE__).'/ProductAttributeListFrontendViewNotChangeable.html');
    }
}
