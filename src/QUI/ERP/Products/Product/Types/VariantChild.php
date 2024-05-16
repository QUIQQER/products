<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantChild
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Field\Types\AttributeGroup;
use QUI\ERP\Products\Field\Types\Folder as FolderProductFieldType;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\VariantGenerating;
use QUI\Exception;
use QUI\Locale;
use QUI\Projects\Media\Image;
use QUI\Projects\Media\Utils as MediaUtils;

use function array_flip;
use function count;
use function implode;
use function in_array;
use function is_null;
use function str_replace;

/**
 * Class VariantChild
 * - Variant Child
 *
 * @package QUI\ERP\Products\Product\Types
 */
class VariantChild extends AbstractType
{
    /**
     * @var VariantParent|null
     */
    protected ?VariantParent $Parent = null;

    /**
     * @var null|QUI\ERP\Products\Field\Field
     */
    protected mixed $OwnMediaFolderField = null;

    protected ?array $shortDescAddition = null;

    /**
     * VariantChild constructor.
     *
     * @param $pid
     * @param array $product
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function __construct($pid, array $product = [])
    {
        parent::__construct($pid, $product);

        if (isset($this->fields[Fields::FIELD_FOLDER])) {
            $MediaField = $this->fields[Fields::FIELD_FOLDER];

            if ($MediaField->getValue()) {
                $this->OwnMediaFolderField = $MediaField;
            }
        }

        // inheritance
        $inheritedFields = QUI\ERP\Products\Utils\Products::getInheritedFieldIdsForProduct($this);
        $inheritedFields = array_flip($inheritedFields);

        $editableFields = QUI\ERP\Products\Utils\Products::getEditableFieldIdsForProduct($this);
        $editableFields = array_flip($editableFields);

        $Parent = $this->getParent();

        if (empty($Parent)) {
            QUI\System\Log::addError(
                QUI::getLocale()->get(
                    'quiqqer/products',
                    'exception.Product.Types.VariantChild.parent_not_found',
                    [
                        'childId' => $pid,
                        'parentId' => $this->getAttribute('parent')
                    ]
                )
            );

            return;
        }

        $fields = $Parent->getFields();

        $attributeListFieldValues = [];

        foreach ($fields as $ParentField) {
            $fieldId = $ParentField->getId();

            if ($this->OwnMediaFolderField && $fieldId === $this->OwnMediaFolderField->getId()) {
                continue;
            }

            $isInherited = isset($inheritedFields[$fieldId]);
            $isEditable = isset($editableFields[$fieldId]);

            try {
                $Field = $this->getField($fieldId);

                if ($isInherited) {
                    $Field->setUnassignedStatus(false);

                    if ($ParentField->isOwnField()) {
                        $Field->setOwnFieldStatus(true);
                    }

                    // If inherited field is not editable by children -> use parent value
                    // Therefore: If an inherited field IS editable -> do not use parent value and keep own value
                    if (!$isEditable) {
                        try {
                            $Field->setValue($ParentField->getValue());
                        } catch (QUI\Exception $Exception) {
                            QUI\System\Log::addDebug($Exception->getMessage());
                        }

                        continue;
                    }
                }

                $isEmpty = $Field->isEmpty();

                // Media folders: isEmpty() of folder fields additionally checks if there are images in the folder.
                // But at this point we are only interested if a field value is set.
                // Therefore, media folder fields are treated special here.
                if ($Field instanceof FolderProductFieldType) {
                    $isEmpty = empty($Field->getValue());
                }

                // If the short description of variant children shall be extended by variant defining
                // attribute list field values, collect these values here.
                if (!$isEmpty) {
                    if (Products::isExtendVariantChildShortDesc() && $Field instanceof AttributeGroup) {
                        $attributeListFieldValues[] = [
                            'title' => $Field->getTitle(),
                            'valueTitle' => $Field->getValueTitle()
                        ];
                    }

                    continue;
                }
            } catch (QUI\Exception) {
                $this->addField($ParentField);

                $Field = $this->getField($fieldId);
                $Field->setUnassignedStatus(false);

                if ($Field->isOwnField()) {
                    $Field->setOwnFieldStatus(true);
                }

                continue;
            }

            // If inherited field is editable but has no own value -> use parent value
            $Field->setValue($ParentField->getValue());
        }

        if (!empty($attributeListFieldValues)) {
            $shortDesc = $this->getFieldValueByLocale(Fields::FIELD_SHORT_DESC);
            $lang = QUI::getLocale()->getCurrent();

            $shortDescLines = [];

            foreach ($attributeListFieldValues as $field) {
                $extend = $field['title'] . ': ' . $field['valueTitle'];

                if (!str_contains($shortDesc, $extend)) {
                    $shortDescLines[] = $extend;
                }
            }

            $shortDescAddition = implode('; ', $shortDescLines);
            $this->shortDescAddition[$lang] = $shortDescAddition;

            if (empty($shortDesc)) {
                $shortDesc = $shortDescAddition;
            } else {
                $shortDesc .= '; ' . $shortDescAddition;
            }

            /** @var QUI\ERP\Products\Field\Types\InputMultiLang $ShortDescField */
            $ShortDescField = $this->getField(Fields::FIELD_SHORT_DESC);
            $ShortDescField->setValueByLocale($shortDesc);
        }
    }

    //region type stuff

    /**
     * @param null $Locale
     * @return string
     */
    public static function getTypeTitle($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.child.title');
    }

    /**
     * @param null $Locale
     * @return string
     */
    public static function getTypeDescription($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.child.title');
    }

    /**
     * @return bool
     */
    public static function isTypeSelectable(): bool
    {
        return false;
    }

    //endregion

    //region product methods

    /**
     * Return the parent variant product
     *
     * @return VariantParent|null
     */
    public function getParent(): ?VariantParent
    {
        if ($this->Parent !== null) {
            return $this->Parent;
        }

        try {
            $Parent = Products::getProduct($this->getAttribute('parent'));

            if (!($Parent instanceof VariantParent)) {
                QUI\System\Log::addError('Child parent is no VariantParent', [
                    'parentId' => $Parent->getId(),
                    'childId' => $this->getId()
                ]);

                return null;
            }

            $this->Parent = $Parent;
        } catch (QUI\Exception) {
        }

        return $this->Parent;
    }

    /**
     * Return the title
     *
     * @param null|QUI\Locale $Locale
     * @return string
     */
    public function getTitle(QUI\Locale $Locale = null): string
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_TITLE, $Locale);

        if (!empty($result)) {
            return $result;
        }

        return $this->getParent()->getTitle($Locale);
    }

    /**
     * Return the title
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(QUI\Locale $Locale = null): string
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_SHORT_DESC, $Locale);

        $contentCheck = strip_tags($result);
        $contentCheck = trim($contentCheck);

        if (!empty($contentCheck)) {
            return $result;
        }

        return $this->getParent()->getDescription($Locale);
    }

    /**
     * Return the product content
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getContent(QUI\Locale $Locale = null): string
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_CONTENT, $Locale);

        $contentCheck = strip_tags($result);
        $contentCheck = trim($contentCheck);

        if (!empty($contentCheck)) {
            return $result;
        }

        return $this->getParent()->getContent($Locale);
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->getParent()->getCategories();
    }

    /**
     * @return QUI\ERP\Products\Interfaces\CategoryInterface|null
     */
    public function getCategory(): ?QUI\ERP\Products\Interfaces\CategoryInterface
    {
        return $this->getParent()->getCategory();
    }

    /**
     * @return Image
     * @throws Exception
     */
    public function getImage(): QUI\Projects\Media\Image
    {
        try {
            $Image = parent::getImage();
        } catch (QUI\Exception) {
            return $this->getParent()->getImage();
        }

        try {
            $Project = QUI::getRewrite()->getProject();
            $Media = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder && $Placeholder->getId() !== $Image->getId()) {
                return $Image;
            }

            return $Image;
        } catch (QUI\Exception) {
        }

        return $this->getParent()->getImage();
    }

    /**
     * Return the product media folder
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception|QUI\ERP\Products\Product\Exception
     */
    public function getMediaFolder(): QUI\Projects\Media\Folder
    {
        try {
            if ($this->OwnMediaFolderField) {
                $folderUrl = $this->OwnMediaFolderField->getValue();
            } else {
                $folderUrl = $this->getFieldValue(Fields::FIELD_FOLDER);
            }

            $Folder = MediaUtils::getMediaItemByUrl($folderUrl);

            if ($Folder instanceof QUI\Projects\Media\Folder) {
                return $Folder;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        return parent::getMediaFolder();
    }

    /**
     * Has the variant its own media folder
     *
     * @return bool
     */
    public function hasOwnMediaFolder(): bool
    {
        if ($this->OwnMediaFolderField) {
            return true;
        }

        return false;
    }

    /**
     * @return QUI\Projects\Media\Folder
     * @return QUI\Projects\Media\Folder
     *
     * @throws QUI\Exception
     */
    public function createOwnMediaFolder(): QUI\Projects\Media\Folder
    {
        if (!$this->OwnMediaFolderField) {
            $this->OwnMediaFolderField = $this->getField(Fields::FIELD_FOLDER);
            $this->OwnMediaFolderField->clearValue();
        }

        $fieldId = $this->OwnMediaFolderField->getId();
        $Field = $this->getField($fieldId);

        if ($Field->getType() != Fields::TYPE_FOLDER) {
            throw new QUI\ERP\Products\Product\Exception([
                'quiqqer/products',
                'exception.product.field.is.no.media.folder'
            ]);
        }

        // exist a media folder in the field?
        try {
            $folderUrl = $this->getFieldValue($fieldId);
            $Folder = MediaUtils::getMediaItemByUrl($folderUrl);

            if ($Folder instanceof QUI\Projects\Media\Folder) {
                return $Folder;
            }
        } catch (QUI\Exception) {
        }


        // create folder
        $Parent = Products::getParentMediaFolder();

        try {
            $productId = $this->getId();

            if ($Parent->childWithNameExists($productId)) {
                $Folder = $Parent->getChildByName($productId);
            } else {
                $Folder = $Parent->createFolder($this->getId());
                $Folder->setAttribute('order', 'priority ASC');
                $Folder->save();
            }
        } catch (QUI\Exception $Exception) {
            if ($Exception->getCode() != 701) {
                throw $Exception;
            }

            $Folder = $Parent->getChildByName($this->getId());
        }

        if (!$Folder instanceof QUI\Projects\Media\Folder) {
            throw new QUI\ERP\Products\Product\Exception(
                'Could not find a media folder'
            );
        }

        $Field = $this->getField(Fields::FIELD_FOLDER);
        $Field->setValue($Folder->getUrl());

        $this->update();

        QUI::getEvents()->fireEvent('onQuiqqerProductsProductCreateMediaFolder', [$this]);

        return $Folder;
    }

    /**
     * Return all images of the product
     * The Variant Parent return all images of the children, too
     *
     * @param array $params - optional, select params
     * @return array
     */
    public function getImages(array $params = []): array
    {
        try {
            $images = $this->getMediaFolder()->getImages($params);

            if (count($images)) {
                return $images;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        return $this->getParent()->getImages($params);
    }

    /**
     * Return a variant children by its variant field hash
     * getVariantByVariantHash will be executed at the parent product
     *
     * @param string $hash
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getVariantByVariantHash(string $hash): AbstractType
    {
        return $this->getParent()->getVariantByVariantHash($hash);
    }

    /**
     * Returns the backend panel control
     */
    public static function getTypeBackendPanel(): string
    {
        return 'package/quiqqer/products/bin/controls/products/ProductVariant';
    }

    /**
     * Generate a variant hash for this variant child
     * The variant hash depends on the used fields
     *
     * hash = ;fieldID:fieldValue;fieldID:fieldValue;fieldID:fieldValue;
     *
     * @return string
     */
    public function generateVariantHash(): string
    {
        $Parent = $this->getParent();
        $fields = VariantGenerating::getInstance()->getFieldsForGeneration($Parent);

        $hashFields = [];

        foreach ($fields as $Field) {
            try {
                $VariantField = $this->getField($Field->getId());
                $hashFields[] = $VariantField;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return QUI\ERP\Products\Utils\Products::generateVariantHashFromFields($hashFields);
    }

    /**
     * @return array
     */
    public function availableActiveChildFields(): array
    {
        return $this->getParent()->availableActiveChildFields();
    }

    /**
     * @return array
     */
    public function availableActiveFieldHashes(): array
    {
        return $this->getParent()->availableActiveFieldHashes();
    }

    /**
     * @param array $fieldData
     * @param QUI\Interfaces\Users\User|null $EditUser
     *
     * @throws QUI\Database\Exception
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    protected function productSave(array $fieldData, QUI\Interfaces\Users\User $EditUser = null): void
    {
        // check fields with parent fields
        $Parent = $this->getParent();
        $inheritedFieldIds = QUI\ERP\Products\Utils\Products::getInheritedFieldIdsForProduct($this);

        foreach ($fieldData as $k => $field) {
            $fieldId = (int)$field['id'];

            try {
                $FieldParent = $Parent->getField($fieldId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
                continue;
            }

            if (!is_null($this->shortDescAddition) && $fieldId === Fields::FIELD_SHORT_DESC) {
                $fieldValue = $field['value'];

                foreach ($this->shortDescAddition as $lang => $addition) {
                    if (isset($fieldValue[$lang])) {
                        $fieldValue[$lang] = str_replace('; ' . $addition, '', $fieldValue[$lang]);
                    }
                }

                $field['value'] = $fieldValue;
                $fieldData[$k]['value'] = $fieldValue;
            }

            $parentFieldValue = $FieldParent->getValue();

            /*
             * Only save field values that are different from the parent (if the field is inherited!)
             */
            if (
                $fieldData[$k]['type'] !== Fields::TYPE_ATTRIBUTE_GROUPS &&
                $field['value'] === $parentFieldValue &&
                in_array($fieldId, $inheritedFieldIds)
            ) {
                $fieldData[$k]['value'] = null;
            }
        }


        parent::productSave($fieldData, $EditUser);

        /*
         * Set AttributeGroup field data to images.
         *
         * This is done so that a click on a variant image in the frontend
         * loads the corresponding specific variant child.
         */
        if ($this->OwnMediaFolderField instanceof QUI\ERP\Products\Field\Types\Folder) {
            $attributeGroupFieldData = [];

            /** @var QUI\ERP\Products\Field\Types\AttributeGroup $AttributeGroupField */
            foreach ($this->getFieldsByType(Fields::TYPE_ATTRIBUTE_GROUPS) as $AttributeGroupField) {
                $fieldId = $AttributeGroupField->getId();
                $fieldValue = $this->getFieldValue($fieldId);

                if (!empty($fieldValue) && !empty($AttributeGroupField->getOption('is_image_attribute'))) {
                    $attributeGroupFieldData[$fieldId] = $fieldValue;
                }
            }

            if (!empty($attributeGroupFieldData)) {
                $QuiMediaFolder = $this->OwnMediaFolderField->getMediaFolder();

                if ($QuiMediaFolder) {
                    /** @var QUI\Projects\Media\Image $Image */
                    foreach ($QuiMediaFolder->getImages() as $Image) {
                        $Image->setAttribute(Fields::MEDIA_ATTR_IMAGE_ATTRIBUTE_GROUP_DATA, $attributeGroupFieldData);
                        $Image->save($EditUser);
                    }
                }
            }
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            ['variantHash' => $this->generateVariantHash()],
            ['id' => $this->getId()]
        );
    }

    /**
     * return all available fields from the variant children
     * this array contains all field ids and field values that are in use in the children
     *
     * @return array
     */
    public function availableChildFields(): array
    {
        return $this->getParent()->availableChildFields();
    }

    //endregion
}
