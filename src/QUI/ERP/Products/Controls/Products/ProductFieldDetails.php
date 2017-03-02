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
            'Product' => false,
            'files'   => true, // show in a FIELD_FOLDER all files
            'images'  => true  // show in a FIELD_FOLDER all images
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

        $Engine = QUI::getTemplateManager()->getEngine();

        /* @var $Field QUI\ERP\Products\Field\Field */
        switch ($Field->getType()) {
            case QUI\ERP\Products\Handler\Fields::TYPE_TEXTAREA:
            case QUI\ERP\Products\Handler\Fields::TYPE_TEXTAREA_MULTI_LANG:
                $template = dirname(__FILE__) . '/ProductFieldDetails.Content.html';
                break;

            case QUI\ERP\Products\Handler\Fields::TYPE_FOLDER:
                /* @var $Field QUI\ERP\Products\Field\Types\Folder */
                $template = dirname(__FILE__) . '/ProductFieldDetails.MediaFolder.html';
                $Folder   = $Field->getMediaFolder();
                $files    = array();

                $showFiles  = $this->getAttribute('files');
                $showImages = $this->getAttribute('images');

                if (!$Folder) {
                    return '';
                }

                if ($showFiles && $showImages) {
                    $files = $Folder->getChildren();
                } elseif (!$showFiles && $showImages) {
                    $files = $Folder->getImages();
                } elseif ($showFiles && !$showImages) {
                    $files = $Folder->getFiles();
                }

                $files = array_filter($files, function ($File) {
                    /* @var $File QUI\Projects\Media\Item $File */
                    return $File->isActive();
                });

                $Engine->assign(array(
                    'Utils'  => new QUI\Projects\Media\Utils(),
                    'Folder' => $Folder,
                    'files'  => $files
                ));

                break;

            default:
                return '';
        }

        $Engine->assign(array(
            'this'    => $this,
            'Field'   => $Field,
            'Product' => $this->getAttribute('Product')
        ));

        return $Engine->fetch($template);
    }
}
