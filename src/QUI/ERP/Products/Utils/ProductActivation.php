<?php

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Model;
use QUI\Projects\Media\Image;
use QUI\Projects\Media\Utils as MediaUtils;

final class ProductActivation
{
    /**
     * Include the explicitly selected main image even when it is outside the product folder.
     *
     * @return array<string, Image>
     */
    public static function getInactiveImages(Model $Product): array
    {
        $images = $Product->getImages(['active' => 0]);

        try {
            $mainImage = $Product->getFieldValue(Fields::FIELD_IMAGE);

            if (is_string($mainImage) && $mainImage !== '') {
                $images[] = MediaUtils::getImageByUrl($mainImage);
            }
        } catch (QUI\Exception) {
            // Products without an explicit main image can still have gallery images.
        }

        $inactiveImages = [];

        foreach ($images as $Image) {
            if (
                !$Image instanceof Image
                || $Image->isActive()
                || $Image->isDeleted()
            ) {
                continue;
            }

            $key = $Image->getProject()->getName() . ':' . $Image->getId();
            $inactiveImages[$key] = $Image;
        }

        return $inactiveImages;
    }

    public static function activate(Model $Product, bool $activateImages = false): void
    {
        $images = $activateImages ? self::getInactiveImages($Product) : [];

        // Check media permissions before making any changes to this product.
        foreach ($images as $Image) {
            $Image->checkPermission('quiqqer.projects.media.edit');
        }

        $Product->activate();

        foreach ($images as $Image) {
            $Image->activate();
        }

        if ($images !== []) {
            $Product->updateCache();
        }
    }
}
