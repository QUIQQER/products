<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

use function dirname;
use function file_get_contents;

/**
 * Class UserInputFrontendView - Frontend VIEW
 */
class UserInputFrontendView extends QUI\ERP\Products\Field\View
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

        $id = $this->getId();
        $options = $this->getOptions();
        $name = 'field-' . $id;

        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'css' => file_get_contents(dirname(__FILE__) . '/UserInputFrontendView.css'),
            'this' => $this,
            'id' => $id,
            'title' => $this->getTitle(),
            'name' => $name,
            'isRequired' => $this->isRequired(),
            'options' => $options
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/UserInputFrontendView.html');
    }
}
