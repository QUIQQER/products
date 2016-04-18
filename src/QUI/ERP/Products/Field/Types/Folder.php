<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Folder
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\ERP\Products\Field\View;

/**
 * Class Input
 * @package QUI\ERP\Products\Field
 */
class Folder extends QUI\ERP\Products\Field\Field
{
    protected $searchable = false;
    
    public function getBackendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    public function getFrontendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Folder';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        if (is_null($value)) {
            return;
        }
        
        try {
            $MediaItem = MediaUtils::getMediaItemByUrl($value);

            if (!MediaUtils::isFolder($MediaItem)) {
                throw new QUI\Exception();
            }
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                )
            ));
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return string
     */
    public function cleanup($value)
    {
        try {
            $MediaItem = MediaUtils::getMediaItemByUrl($value);
        } catch (QUI\Exception $Exception) {
            return null;
        }

        if (!MediaUtils::isFolder($MediaItem)) {
            return null;
        }

        return $MediaItem->getUrl();
    }
}
