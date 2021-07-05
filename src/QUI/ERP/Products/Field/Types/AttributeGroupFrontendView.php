<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Handler\Fields;

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

        if ($id === QUI\ERP\Products\Handler\Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES &&
            $this->Product instanceof QUI\ERP\Products\Product\Types\VariantParent) {
            $variants = $this->Product->getVariants();
            $entries  = [];

            foreach ($variants as $Variants) {
                $entries[] = [
                    'title'    => $Variants->getField(Fields::FIELD_TITLE)->getValue(),
                    'valueId'  => $Variants->getId(),
                    'selected' => false,
                    'hide'     => false,
                    'disabled' => false
                ];
            }
        } elseif ($id === QUI\ERP\Products\Handler\Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES &&
                  $this->Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $currentId = $this->Product->getId();
            $Variant   = $this->Product->getParent();
            $variants  = $Variant->getVariants();
            $entries   = [];

            foreach ($variants as $Variants) {
                $entries[] = [
                    'title'    => $Variants->getField(Fields::FIELD_TITLE)->getValue(),
                    'valueId'  => $Variants->getId(),
                    'selected' => $currentId === $Variants->getId(),
                    'hide'     => false,
                    'disabled' => false
                ];

                if ($currentId === $Variants->getId()) {
                    $value = $currentId;
                }
            }
        }


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

        if (!isset($entries[0])) {
            $text = '---';
        } else {
            $option = $entries[0];

            foreach ($entries as $entry) {
                if ($entry['valueId'] === $value) {
                    $option = $entry;
                    break;
                }
            }

            $title = $option['title'];
            $text  = '';

            if (\is_string($title)) {
                $text = $title;
            } elseif (isset($title[$current])) {
                $text = $title[$current];
            } elseif (isset($title[$currentLC])) {
                $text = $title[$currentLC];
            }
        }

        $Engine->assign('valueText', $text);

        return $Engine->fetch(\dirname(__FILE__).'/AttributeGroupFrontendViewNotChangeable.html');
    }
}
