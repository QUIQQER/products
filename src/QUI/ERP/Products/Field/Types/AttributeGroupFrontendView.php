<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Accounting\Calc as ErpCalc;

/**
 * Class AttributeGroupFrontendView
 *
 * @package QUI\ERP\Products\Field\Types
 */
class AttributeGroupFrontendView extends QUI\ERP\Products\Field\View
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

        $hasDefault = function () use ($entries) {
            foreach ($entries as $key => $option) {
                if (isset($option['selected']) && $option['selected']) {
                    return true;
                }
            }

            return false;
        };

        if ($value === '' && !$hasDefault()) {
            $options[] = [
                'value'    => '',
                'text'     => QUI::getLocale()->get(
                    'quiqqer/products',
                    'fieldtype.ProductAttributeList.select.emptyvalue'
                ),
                'selected' => '',
                'data'     => ''
            ];
        }

        $currentLC = \strtolower($current).'_'.\strtoupper($current);
        $Calc      = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());

        foreach ($entries as $key => $option) {
            $title = $option['title'];

            $text      = '';
            $selected  = '';
            $userInput = '';

            if (isset($option['selected']) && $option['selected']
                || $value == $key
            ) {
                $selected = 'selected="selected" ';
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

            $options[] = [
                'selected' => $selected,
                'value'    => $key,
                'text'     => $text,
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
