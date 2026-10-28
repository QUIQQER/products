<?php

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Model;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Image;
use QUI\Projects\Media\Utils as MediaUtils;

final class ProductMediaCopy
{
    public const COPY = 'copy';
    public const SHARE = 'share';
    public const EMPTY = 'empty';

    public static function validateMode(string $mode): void
    {
        if (!in_array($mode, [self::COPY, self::SHARE, self::EMPTY], true)) {
            throw new QUI\Exception(['quiqqer/products', 'products.copy.media.invalidMode']);
        }
    }

    public static function apply(Model $Source, Model $Target, string $mode): void
    {
        if ($mode === self::SHARE) {
            return;
        }

        $TargetFolder = $Target instanceof VariantChild
            ? $Target->createOwnMediaFolder()
            : $Target->createMediaFolder();

        if ($mode === self::EMPTY) {
            return;
        }

        $images = [];
        $folders = [];

        try {
            $SourceFolder = $Source->getMediaFolder();
        } catch (QUI\Exception) {
            $SourceFolder = null;
        }

        if ($SourceFolder !== null) {
            self::copyImages($SourceFolder, $TargetFolder, $images, $folders);
        }

        foreach ($Source->getFieldsByType(Fields::TYPE_IMAGE) as $Field) {
            $value = $Field->getValue();

            if (!is_string($value) || $value === '') {
                continue;
            }

            $Image = MediaUtils::getImageByUrl($value);
            $key = self::key($Image);

            if (!isset($images[$key])) {
                $images[$key] = self::copyImage($Image, $TargetFolder);
            }

            $Target->getField($Field->getId())->setValue($images[$key]->getUrl());
        }

        foreach ($Source->getFieldsByType(Fields::TYPE_FOLDER) as $Field) {
            $value = $Field->getValue();

            if (!is_string($value) || $value === '') {
                continue;
            }

            $Folder = MediaUtils::getMediaItemByUrl($value);
            $key = self::key($Folder);

            if (isset($folders[$key])) {
                $Target->getField($Field->getId())->setValue($folders[$key]->getUrl());
            }
        }
    }

    /**
     * Protect folder and image references, including references to descendant folders.
     */
    public static function isFolderShared(Folder $Folder, int $excludedProductId): bool
    {
        $Connection = QUI::getDataBaseConnection();
        $table = QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName());
        $fieldData = $Connection->getDatabasePlatform()->quoteSingleIdentifier('fieldData');
        $Result = $Connection->createQueryBuilder()
            ->select($fieldData)
            ->from($table)
            ->where('id <> :productId')
            ->andWhere($fieldData . ' LIKE :project')
            ->setParameter('productId', $excludedProductId)
            ->setParameter('project', '%' . $Folder->getProject()->getName() . '%')
            ->executeQuery();

        try {
            foreach ($Result->iterateAssociative() as $row) {
                $fields = json_decode((string)$row['fieldData'], true);

                if (!is_array($fields)) {
                    // An unreadable reference must not cause another product's media to be deleted.
                    return true;
                }

                foreach ($fields as $field) {
                    if (
                        !is_array($field)
                        || !in_array($field['type'] ?? '', [Fields::TYPE_IMAGE, Fields::TYPE_FOLDER], true)
                        || !is_string($field['value'] ?? null)
                        || $field['value'] === ''
                    ) {
                        continue;
                    }

                    try {
                        $Item = MediaUtils::getMediaItemByUrl($field['value']);
                    } catch (QUI\Exception) {
                        continue;
                    }

                    if (
                        $Item->getProject()->getName() === $Folder->getProject()->getName()
                        && ($Item->getId() === $Folder->getId()
                            || in_array($Folder->getId(), $Item->getParentIds(), true))
                    ) {
                        return true;
                    }
                }
            }
        } finally {
            $Result->free();
        }

        return false;
    }

    /**
     * @param array<string, Image> $images
     * @param array<string, Folder> $folders
     */
    private static function copyImages(Folder $Source, Folder $Target, array &$images, array &$folders): void
    {
        $key = self::key($Source);

        if (isset($folders[$key])) {
            throw new QUI\Exception('Cycle in product media folders.');
        }

        $folders[$key] = $Target;

        foreach ($Source->getChildren() as $Item) {
            if ($Item instanceof Image) {
                $images[self::key($Item)] = self::copyImage($Item, $Target);
            } elseif ($Item instanceof Folder) {
                $Copy = $Target->createFolder($Item->getAttribute('name'));
                self::copyImages($Item, $Copy, $images, $folders);
            }
        }
    }

    private static function copyImage(Image $Image, Folder $Target): Image
    {
        $Copy = $Image->copyTo($Target);

        if (!$Copy instanceof Image) {
            throw new QUI\Exception('The copied product image is not an image.');
        }

        $Copy->setAttribute('priority', $Image->getAttribute('priority'));
        $Copy->save();

        if ($Image->isActive()) {
            $Copy->activate();
        } else {
            $Copy->deactivate();
        }

        return $Copy;
    }

    private static function key(QUI\Interfaces\Projects\Media\File $Item): string
    {
        return $Item->getProject()->getName() . ':' . $Item->getId();
    }
}
