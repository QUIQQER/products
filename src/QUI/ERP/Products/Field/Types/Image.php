<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Image
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;
use QUI\Projects\Media\Utils as MediaUtils;

/**
 * Class Input
 * @package QUI\ERP\Products\Field
 */
class Image extends QUI\ERP\Products\Field\Field
{
    protected string $columnType = 'BIGINT(20)';
    protected bool $searchable = false;

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
        return new ImageFrontendView($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Image';
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

            if (!MediaUtils::isImage($MediaItem)) {
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

        if (!MediaUtils::isImage($MediaItem)) {
            return null;
        }

        return $MediaItem->getUrl();
    }
}
