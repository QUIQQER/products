<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class AttributeGroupFrontendView
 *
 * @package QUI\ERP\Products\Field\Types
 */
class AttributeGroupFrontendValueView extends QUI\ERP\Products\Field\View
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
        $entries = [];

        if (isset($options['entries'])) {
            $entries = $options['entries'];
        }

        if (!\is_string($value) && !\is_numeric($value)) {
            $value = '';
        }

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }

        foreach ($entries as $entry) {
            if ($entry['valueId'] !== $value) {
                continue;
            }

            if (isset($entry['titles']['title'][$current])) {
                $value = $entry['titles']['title'][$current];
                break;
            }

            break;
        }

        $Engine->assign([
            'this'          => $this,
            'id'            => $id,
            'title'         => $this->getTitle(),
            'name'          => $name,
            'value'         => $value
        ]);


        return $Engine->fetch(\dirname(__FILE__).'/AttributeGroupFrontendValueView.html');
    }
}
