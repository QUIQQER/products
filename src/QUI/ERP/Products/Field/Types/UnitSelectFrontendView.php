<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class UnitSelectFrontendView
 *
 * View control for showing UnitSelect values in the product frontend
 */
class UnitSelectFrontendView extends QUI\ERP\Products\Field\View
{
    /**
     * Render the view, return the html
     *
     * @return string
     * @throws QUI\Exception
     */
    public function create()
    {
        if (!$this->hasViewPermission()) {
            return '';
        }

        $Engine  = QUI::getTemplateManager()->getEngine();
        $lang    = QUI::getLocale()->getCurrent();
        $value   = $this->getValue();
        $options = $this->getOptions();

        $displayValue = '-';

        if (!empty($value)) {
            $index    = (int)$value['index'];
            $quantity = $value['quantity'];
            $entries  = $options['entries'];

            foreach ($entries as $k => $entry) {
                if ((int)$k !== $index) {
                    continue;
                }

                if (!empty($entry['title'][$lang])) {
                    $title = $entry['title'][$lang];
                } else {
                    $title = array_shift($entry['title']);
                }

                if ($entry['quantityInput'] && !empty($quantity)) {
                    $displayValue = $quantity.' '.$title;
                } else {
                    $displayValue = $title;
                }
            }
        }

        $Engine->assign([
            'title' => $this->getTitle(),
            'value' => $displayValue
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/UnitSelectFrontendView.html');
    }
}
