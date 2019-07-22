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

        $Engine = QUI::getTemplateManager()->getEngine();

        /** @var UnitSelect $Field */
        $Field = QUI\ERP\Products\Handler\Fields::getField($this->getId());

        $Engine->assign([
            'title' => $this->getTitle(),
            'value' => $Field->getTitleByValue($this->getValue())
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/UnitSelectFrontendView.html');
    }
}
