<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class MultilangFrontendView
 *
 * Default view control for fields with multilang values.
 */
class MultilangFrontendView extends QUI\ERP\Products\Field\View
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
        $value   = $this->getValue();
        $current = QUI::getLocale()->getCurrent();

        if (isset($value[$current])) {
            $value = $value[$current];
        } else {
            $value = $value[0];
        }

        $Engine->assign([
            'title' => $this->getTitle(),
            'value' => $value
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/MultilangFrontendView.html');
    }
}
