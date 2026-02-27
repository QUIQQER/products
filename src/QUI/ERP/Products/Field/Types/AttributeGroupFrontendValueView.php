<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

use function dirname;
use function is_numeric;
use function is_string;

/**
 * Class AttributeGroupFrontendView
 */
class AttributeGroupFrontendValueView extends QUI\ERP\Products\Field\View
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

        $current = QUI::getLocale()->getCurrent();

        $id = $this->getId();
        $value = $this->getValue();
        $options = $this->getOptions();
        $name = 'field-' . $id;
        $entries = [];

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        if (!is_string($value) && !is_numeric($value)) {
            $value = '';
        }

        $Engine = QUI::getTemplateManager()->getEngine();

        foreach ($entries as $entry) {
            if ($entry['valueId'] !== $value) {
                continue;
            }

            if (isset($entry['titles']['title'][$current])) {
                $value = $entry['titles']['title'][$current];
                break;
            }

            if (isset($entry['title'][$current])) {
                $value = $entry['title'][$current];
                break;
            }

            break;
        }

        $Engine->assign([
            'this' => $this,
            'id' => $id,
            'title' => $this->getTitle(),
            'name' => $name,
            'value' => $value
        ]);


        return $Engine->fetch(dirname(__FILE__) . '/AttributeGroupFrontendValueView.html');
    }
}
