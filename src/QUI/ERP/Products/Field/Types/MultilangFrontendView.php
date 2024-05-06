<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

use function dirname;

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
     */
    public function create(): string
    {
        if (!$this->hasViewPermission()) {
            return '';
        }

        $Engine = QUI::getTemplateManager()->getEngine();

        /** @var UnitSelect $Field */
        $value = $this->getValue();
        $current = QUI::getLocale()->getCurrent();
        $value = $value[$current] ?? $value[0];

        $Engine->assign([
            'title' => $this->getTitle(),
            'value' => $value
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/MultilangFrontendView.html');
    }
}
