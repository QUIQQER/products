<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class UserInputBackendView - Backend VIEW
 */
class UserInputBackendView extends QUI\ERP\Products\Field\View
{
    /**
     * Render the view, return the html
     *
     * @return string
     */
    public function create()
    {
        $id      = $this->getId();
        $options = $this->getOptions();

        $name = 'field-'.$id;

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            return '';
        }

        $Engine->assign([
            'css'        => \file_get_contents(\dirname(__FILE__).'/UserInputBackendView.css'),
            'this'       => $this,
            'id'         => $id,
            'title'      => $this->getTitle(),
            'name'       => $name,
            'isRequired' => $this->isRequired(),
            'options'    => $options
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/UserInputBackendView.html');
    }
}
