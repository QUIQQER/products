<?php

/**
 * This file contains QUI\ERP\Products\Controls\Products\Product
 */

namespace QUI\ERP\Products\Controls\Products;

use DusanKasan\Knapsack\Collection;
use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\JsonLd;
use QUI\ERP\Products\Utils\Fields as FieldUtils;
use QUI\ERP\Products\Utils\Products as ProductUtils;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Watchlist
 */
class Product extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'Product' => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/products/Product',

            'data-qui-option-show-price' => true,
            'data-qui-option-available' => true
        ]);

        $this->addCSSClass('quiqqer-products-product');
        $this->addCSSFile(\dirname(__FILE__) . '/Product.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see \QUI\Control::create()
     *
     */
    public function getBody()
    {
        /* @var $Product QUI\ERP\Products\Product\Product */
        $Engine = QUI::getTemplateManager()->getEngine();
        $Product = $this->getAttribute('Product');
        $Gallery = new QUI\Gallery\Controls\Slider();
        $fields = [];
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());

        $typeVariantParent = \is_a($Product->getType(), QUI\ERP\Products\Product\Types\VariantParent::class, true);
        $typeVariantChild = \is_a($Product->getType(), QUI\ERP\Products\Product\Types\VariantChild::class, true);

        if ($typeVariantParent) {
            /* @var $Product QUI\ERP\Products\Product\Types\VariantParent */
            $this->setAttributes([
                'data-qui-option-show-price' => false,
                'data-qui-option-available' => false
            ]);

            try {
                // if default attribute exists, the variant has no attribute select fields
                // so we need to use the first variant as product
                $Product->getField(Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES);
                $variants = $Product->getVariants();

                foreach ($variants as $V) {
                    if (!$V->isActive()) {
                        continue;
                    }

                    $Product = $V;

                    $this->setAttributes([
                        'data-qui-option-show-price' => true,
                        'data-qui-option-available' => true
                    ]);

                    $typeVariantChild = true;
                    $typeVariantParent = false;
                    break;
                }
            } catch (QUI\ERP\Products\Product\Exception $Exception) {
                // use default variant, if a default variant exists
                if (!$this->getAttribute('ignoreDefaultVariant') && $Product->getDefaultVariantId()) {
                    try {
                        $Product = $Product->getDefaultVariant();

                        $this->setAttributes([
                            'data-qui-option-show-price' => true,
                            'data-qui-option-available' => true
                        ]);

                        $typeVariantChild = true;
                        $typeVariantParent = false;
                    } catch (QUI\Exception $Exception) {
                        QUI\System\Log::addDebug($Exception);
                    }
                }
            }
        }

        $User = QUI::getUserBySession();

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            $View = $Product->getView();
            $Unique = $Product->createUniqueProduct($User);

            if ($typeVariantParent) {
                $Price = $Product->getMinimumPrice($User);
            } else {
                try {
                    $Price = $Calc->getProductPrice($Unique);
                } catch (QUI\Exception $Exception) {
                    $Price = null;
                    QUI\System\Log::writeException($Exception);
                }
            }
        } else {
            $View = $Product;
            $Price = $Product->getPrice($User);
        }

        if ($typeVariantParent && $Product->getMinimumPrice()->getValue() !== $Product->getMaximumPrice()->getValue()) {
            $Price->enableMinimalPrice();
        }

        /* @var $Product QUI\ERP\Products\Product\UniqueProduct */
        $this->setAttribute('data-productid', $View->getId());

        $productAttributes = isset($Unique) ? $Unique->getAttributes() : $Product->getAttributes();

        // gallery
        $PlaceholderImage = $this->getProject()->getMedia()->getPlaceholderImage();

        if ($PlaceholderImage) {
            $Gallery->setAttribute(
                'placeholderimage',
                $PlaceholderImage->getSizeCacheUrl()
            );

            $Gallery->setAttribute('placeholdercolor', '#fff');
        }

        try {
            $Gallery->setAttribute('folderId', $Product->getFieldValue(Fields::FIELD_FOLDER));
        } catch (QUI\Exception $Exception) {
        }

        if ($typeVariantParent || $typeVariantChild) {
            $Gallery->setAttribute('folderId', false);

            foreach ($this->getVariantImages($Product) as $Image) {
                $Gallery->addImage($Image);
            }
        }

        $Gallery->setAttribute('height', '400px');
        $Gallery->setAttribute('data-qui-options-show-controls-always', 0);
        $Gallery->setAttribute('data-qui-options-show-title-always', 0);
        $Gallery->setAttribute('data-qui-options-show-title', 0);
        $Gallery->setAttribute('data-qui-options-imagefit', 1);

        $Gallery->setAttribute('data-qui-options-preview', 1);
        $Gallery->setAttribute('data-qui-options-preview-outside', 1);
        $Gallery->setAttribute('data-qui-options-preview-background-color', '#fff');
        $Gallery->setAttribute('data-qui-options-preview-color', '#ddd');

        // fields - fields for the product header
        $displayedFields = [
            Fields::FIELD_PRODUCT_NO
        ];

        foreach ($displayedFields as $field) {
            if ($View->getField($field)) {
                $fields[] = $View->getField($field);
            }
        }

        // fields for the details
        $details = \array_filter($View->getFields(), function ($Field) {
            /* @var $Field QUI\ERP\Products\Field\View */
            if (!QUI\ERP\Products\Utils\Fields::showFieldInProductDetails($Field)) {
                return false;
            }

            return $Field->hasViewPermission();
        });

        $vatArray = [];

        if (isset($productAttributes['calculated_vatArray'])) {
            $vatArray = $productAttributes['calculated_vatArray'];
        }

        // pricedisplay
        $PriceDisplay = new QUI\ERP\Products\Controls\Price([
            'Price' => $Price,
            'withVatText' => true,
            'Calc' => $Calc,
            'vatArray' => $vatArray
        ]);

        // retail price (UVP)
        $PriceRetailDisplay = false;

        if (isset($Unique)) {
            $PriceRetail = $Unique->getCalculatedPrice(Fields::FIELD_PRICE_RETAIL)->getPrice();
        } else {
            $PriceRetail = $Product->createUniqueProduct()
                ->getCalculatedPrice(Fields::FIELD_PRICE_RETAIL)
                ->getPrice();
        }


        if ($Product->getFieldValue('FIELD_PRICE_RETAIL')) {
            $PriceRetailDisplay = new QUI\ERP\Products\Controls\Price([
                'Price' => $PriceRetail,
                'withVatText' => false
            ]);
        }

        // offer price (Angebotspreis)
        $PriceOldDisplay = false;

        if ($View->hasOfferPrice()) {
            $PriceOldDisplay = new QUI\ERP\Products\Controls\Price([
                'Price' => new QUI\ERP\Money\Price(
                    $View->getOriginalPrice()->getValue(),
                    QUI\ERP\Currency\Handler::getDefaultCurrency()
                ),
                'withVatText' => false
            ]);
        }

        // file / image folders
        $detailFields = [];

        $fieldsList = \array_merge(
            $Product->getFieldsByType(Fields::TYPE_FOLDER),
            $Product->getFieldsByType(Fields::TYPE_TEXTAREA),
            $Product->getFieldsByType(Fields::TYPE_TEXTAREA_MULTI_LANG),
            $Product->getFieldsByType(Fields::TYPE_PRODCUCTS)
        );

        /* @var $Field QUI\ERP\Products\Field\Types\Folder */
        foreach ($fieldsList as $Field) {
            if ($Field->getId() == Fields::FIELD_FOLDER || $Field->getId() == Fields::FIELD_CONTENT) {
                continue;
            }

            if (!$Field->hasViewPermission()) {
                continue;
            }

            if (!$Field->isPublic()) {
                continue;
            }

            // Special handling for some field types
            switch ($Field->getType()) {
                case Fields::TYPE_FOLDER:
                    $showIfEmpty = $Field->getOption('showFrontendTabIfEmpty');

                    if (!$showIfEmpty && $Field->isEmpty()) {
                        continue 2;
                    }
                    break;
            }

            $detailFields[] = $Field;
        }

        // product fields
        $productFields = [];
        $productFieldList = \array_filter($View->getFields(), function ($Field) {
            /* @var $Field QUI\ERP\Products\Field\View */
            if ($Field->getType() == Fields::TYPE_PRODCUCTS) {
                return true;
            }

            return false;
        });

        foreach ($productFieldList as $Field) {
            /* @var $Field QUI\ERP\Products\Field\View */
            if (!$Field->getValue()) {
                continue;
            }

            if (!$Field->showInDetails()) {
                continue;
            }

            $Slider = new ChildrenSlider();
            $Slider->addProducts($Field->getValue());

            $productFields[] = [
                'Field' => $Field,
                'Slider' => $Slider
            ];
        }

        $Engine->assign([
            'productFields' => $productFields
        ]);

        // Product File List
        $Files = null;

        if (\count($Product->getFiles())) {
            $Files = new ProductFieldDetails([
                'Field' => $Product->getField(Fields::FIELD_FOLDER),
                'Product' => $Product,
                'files' => true,
                'images' => false
            ]);
        }

        if ($typeVariantParent || $typeVariantChild) {
            ProductUtils::setAvailableFieldOptions($Product);

            QUI::getEvents()->addEvent(
                'onQuiqqer::products::product::end',
                function (\quiqqer\smarty4\src\Quiqqer\Engine\Collector $Collector) use ($Product) {
                    $fieldHashes = ProductUtils::getJsFieldHashArray($Product);
                    $fieldHashes = \json_encode($fieldHashes);

                    $availableHashes = $Product->availableActiveFieldHashes();
                    $availableHashes = \array_flip($availableHashes);
                    $availableHashes = \json_encode($availableHashes);

                    $Collector->append('<script>var fieldHashes = ' . $fieldHashes . '</script>');
                    $Collector->append('<script>var availableHashes = ' . $availableHashes . '</script>');
                }
            );
        }

        $Engine->assign([
            'jsonLd' => JsonLd::getJsonLd($Product),
            'Product' => $View,
            'Gallery' => $Gallery,
            'Files' => $Files,
            'fields' => FieldUtils::sortFields($fields),
            'details' => FieldUtils::sortFields($details),
            'detailFields' => FieldUtils::sortFields($detailFields),
            'productAttributeList' => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST),
            'userInputFields' => $View->getFieldsByType(Fields::TYPE_USER_INPUT),
            'productAttributeGroups' => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_GROUPS),
            'Price' => $Price,
            'PriceDisplay' => $PriceDisplay,
            'PriceRetailDisplay' => $PriceRetailDisplay,
            'PriceRetail' => $PriceRetail,
            'PriceOldDisplay' => $PriceOldDisplay,
            'VisitedProducts' => new VisitedProducts(),
            'MediaUtils' => new QUI\Projects\Media\Utils(),
            'Site' => $this->getSite()
        ]);

        // button list
        $Buttons = new Collection([]);

        QUI::getEvents()->fireEvent(
            'quiqqerProductsProductViewButtons',
            [$View, &$Buttons, $this]
        );

        QUI::getEvents()->fireEvent(
            'quiqqerProductsProductViewButtonsAfter',
            [$View, &$Buttons, $this]
        );

        $Engine->assign('Buttons', $Buttons);

        $Engine->assign(
            'buttonsHtml',
            $Engine->fetch(\dirname(__FILE__) . '/Product.Buttons.html')
        );

        // normal product
        if (!$typeVariantParent && !$typeVariantChild) {
            return $Engine->fetch(\dirname(__FILE__) . '/Product.html');
        }

        // variant product
        $this->setAttribute('data-qui', 'package/quiqqer/products/bin/controls/frontend/products/ProductVariant');

        foreach ($this->getVariantControlSettings() as $k => $v) {
            $this->setAttribute('data-qui-options-' . $k, $v);
        }

        return $Engine->fetch(\dirname(__FILE__) . '/ProductVariant.html');
    }

    /**
     * Get settings for the product variant frontend control.
     *
     * package/quiqqer/products/bin/controls/frontend/products/ProductVariant
     *
     * @return array
     */
    public function getVariantControlSettings(): array
    {
        $controlSettings = [];

        $Conf = QUI\ERP\Products\Utils\Package::getConfig();
        $linkVariantsWithImages = !empty($Conf->getValue('variants', 'linkVariantChildrenWithImages'));
        $images = $this->getVariantImages($this->getAttribute('Product'));

        if (!empty($images) && $linkVariantsWithImages) {
            $imageAttributeGroupsData = [];

            /** @var QUI\Projects\Media\Image $Image */
            foreach ($images as $Image) {
                $imageAttributeGroupData = $Image->getAttribute(
                    Fields::MEDIA_ATTR_IMAGE_ATTRIBUTE_GROUP_DATA
                );

                if (!empty($imageAttributeGroupData)) {
                    $imageAttributeGroupsData[$Image->getPath()] = $imageAttributeGroupData;
                }
            }

            $controlSettings['image_attribute_data'] = \json_encode($imageAttributeGroupsData);
        }

        $controlSettings['link_images_and_attributes'] = $linkVariantsWithImages ? 1 : 0;

        return $controlSettings;
    }

    /**
     * Get product images (for variant parents and children).
     *
     * @param QUI\ERP\Products\Product\Product $Product
     * @return QUI\Projects\Media\Image[]
     */
    protected function getVariantImages(QUI\ERP\Products\Product\Product $Product): array
    {
        $images = $Product->getImages();

        try {
            $MainImage = $Product->getImage();
            $mainImageId = $MainImage->getId();
            $hasMainImage = false;

            foreach ($images as $Image) {
                if ($Image->getId() === $MainImage->getId()) {
                    $hasMainImage = true;
                    break;
                }
            }

            if (!$hasMainImage) {
                $images[] = $MainImage;
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            $mainImageId = false;
        }

        \usort($images, function ($ImageA, $ImageB) use ($mainImageId) {
            /**
             * @var QUI\Projects\Media\Image $ImageA
             * @var QUI\Projects\Media\Image $ImageB
             */
            if ($ImageA->getId() === $mainImageId) {
                return -1;
            }

            if ($ImageB->getId() === $mainImageId) {
                return 1;
            }

            return 0;
        });

        return $images;
    }

    /**
     * @return mixed|QUI\Projects\Site
     * @throws QUI\Exception
     */
    protected function getSite()
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
