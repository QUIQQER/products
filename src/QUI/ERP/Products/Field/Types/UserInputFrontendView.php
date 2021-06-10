<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class UserInputFrontendView - Frontend VIEW
 *
 *
 *
 * @package QUI\ERP\Products\Field\Types
 */
class UserInputFrontendView extends QUI\ERP\Products\Field\View
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
            'this'       => $this,
            'id'         => $id,
            'title'      => $this->getTitle(),
            'name'       => $name,
            'isRequired' => $this->isRequired(),
            'options'    => $options
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/UserInputFrontendView.html');
    }
}
