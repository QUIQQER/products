<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

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

        if ($this->Product
            && $this->Product instanceof QUI\ERP\Products\Product\Types\Product) {
            return $this->notChangeableDisplay();
        }

        $current = QUI::getLocale()->getCurrent();

        $id      = $this->getId();
        $value   = $this->getValue();
        $options = $this->getOptions();

        $name    = 'field-'.$id;
        $entries = [];

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        $requiredField = '';

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
        $optionsAvailable = false;
        $options          = [];
        $currentLC        = \strtolower($current).'_'.\strtoupper($current);

        foreach ($entries as $key => $option) {
            $title = $option['title'];

            if (isset($option['hide']) && $option['hide']) {
                continue;
            }

            $text      = '';
            $selected  = '';
            $disabled  = '';
            $userInput = '';

            if ($value === null && isset($option['selected']) && $option['selected']
                || $value === $option['valueId']
                || \is_numeric($value) && (int)$value === $option['valueId']) {
                $selected = 'selected="selected" ';
            }

            if (isset($option['disabled']) && $option['disabled'] || $value === $key) {
                $disabled = 'disabled="disabled" ';
                $selected = '';
            } else {
                $optionsAvailable = true;
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
                'disabled' => $disabled,
                'value'    => \htmlspecialchars($option['valueId']),
                'text'     => \htmlspecialchars($text),
                'data'     => $userInput
            ];
        }

        if (!$optionsAvailable) {
            $Conf = QUI\ERP\Products\Utils\Package::getConfig();

            if ($Conf->getValue('variants', 'hideAttributeGroupsWithNoOptions')) {
                return '';
            }
        }

        $Engine->assign('options', $options);

        return $Engine->fetch(\dirname(__FILE__).'/AttributeGroupFrontendView.html');
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

        return $Engine->fetch(\dirname(__FILE__).'/AttributeGroupFrontendViewNotChangeable.html');
    }
}
