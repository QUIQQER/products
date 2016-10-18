<?php

/**
 * This field contains QUI\ERP\Products\Controls\Products
 */
namespace QUI\ERP\Products\Controls\Products;

use QUI;

/**
 * Class ProductFieldDetails
 * @package QUI\ERP\Products\Controls\Products
 */
class ProductFieldDetails extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'Field'   => false,
            'Product' => false
        ));

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Field = $this->getAttribute('Field');

        if (!$Field) {
            return '';
        }

        /* @var $Field QUI\ERP\Products\Field\Field */
        switch ($Field->getType()) {
            case QUI\ERP\Products\Handler\Fields::TYPE_TEXTAREA:
            case QUI\ERP\Products\Handler\Fields::TYPE_TEXTAREA_MULTI_LANG:
                $template = dirname(__FILE__) . '/ProductFieldDetails.Content.html';
                break;

            case QUI\ERP\Products\Handler\Fields::FIELD_FOLDER:
                $template = dirname(__FILE__) . '/ProductFieldDetails.MediaFolder.html';
                break;

            default:
                return '';
        }

        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign(array(
            'this'    => $this,
            'Field'   => $Field,
            'Product' => $this->getAttribute('Product')
        ));

        return $Engine->fetch($template);
    }
}
