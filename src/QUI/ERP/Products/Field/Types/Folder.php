<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Folder
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;
use QUI\Projects\Media\Utils as MediaUtils;

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
    protected string $columnType = 'BIGINT(20)';

    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * GroupList constructor.
     * @param int $fieldId
     * @param array $params
     */
    public function __construct(int $fieldId, array $params)
    {
        $this->setOptions([
            'autoActivateItems' => true,
            'mediaFolder' => false,
            'showFrontendTabIfEmpty' => false
        ]);

        parent::__construct($fieldId, $params);
    }

    /**
     * @return View
     */
    public function getBackendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Folder';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/FolderSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws Exception
     */
    public function validate(mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        try {
            $MediaItem = MediaUtils::getMediaItemByUrl($value);

            if (!MediaUtils::isFolder($MediaItem)) {
                throw new QUI\Exception();
            }
        } catch (QUI\Exception) {
            throw new Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                ]
            ]);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return string|null
     */
    public function cleanup(mixed $value): ?string
    {
        try {
            $MediaItem = MediaUtils::getMediaItemByUrl($value);
        } catch (QUI\Exception) {
            return null;
        }

        if (!MediaUtils::isFolder($MediaItem)) {
            return null;
        }

        return $MediaItem->getUrl();
    }

    /**
     * Check if folder is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        $MediaFolder = $this->getMediaFolder();

        if (!$MediaFolder) {
            return true;
        }

        $filesCount = (int)$MediaFolder->getFiles(['count' => true]);
        $imagesCount = (int)$MediaFolder->getImages(['count' => true]);

        return ($filesCount + $imagesCount) < 1;
    }

    /**
     * Get media folder of this field
     */
    public function getMediaFolder(): ?QUI\Projects\Media\Folder
    {
        try {
            if (!$this->getValue()) {
                return null;
            }

            $MediaItem = MediaUtils::getMediaItemByUrl($this->getValue());

            if ($MediaItem instanceof QUI\Projects\Media\Folder) {
                return $MediaItem;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        return null;
    }

    /**
     * Return the current value
     *
     * @return string|array
     */
    public function getValue(): mixed
    {
        if (!empty($this->getOption('mediaFolder'))) {
            return $this->getOption('mediaFolder');
        }

        return parent::getValue();
    }
}
