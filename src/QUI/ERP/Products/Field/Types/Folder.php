<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Folder
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\ERP\Products\Field\View;

/**
 * Class Folder
 * Product field which specified a media folder
 *
 * @package QUI\ERP\Products\Field
 */
class Folder extends QUI\ERP\Products\Field\Field
{
    /**
     * @var string
     */
    protected $columnType = 'BIGINT(20)';

    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * GroupList constructor.
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setOptions([
            'autoActivateItems' => true
        ]);

        parent::__construct($fieldId, $params);
    }

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Folder';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/FolderSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        try {
            $MediaItem = MediaUtils::getMediaItemByUrl($value);

            if (!MediaUtils::isFolder($MediaItem)) {
                throw new QUI\Exception();
            }
        } catch (QUI\Exception $Exception) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType'  => $this->getType()
                ]
            ]);
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

    /**
     * Get media folder of this field
     *
     * @return QUI\Projects\Media\Folder|false
     */
    public function getMediaFolder()
    {
        try {
            $MediaItem = MediaUtils::getMediaItemByUrl($this->getValue());

            if (MediaUtils::isFolder($MediaItem)) {
                /* @var $MediaItem QUI\Projects\Media\Folder */
                return $MediaItem;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        return false;
    }
}
