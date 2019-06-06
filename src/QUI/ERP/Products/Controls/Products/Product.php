<?php

/**
 * This file contains QUI\ERP\Products\Controls\Products\Product
 */

namespace QUI\ERP\Products\Controls\Products;

use DusanKasan\Knapsack\Collection;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Utils\Fields as FieldUtils;

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
            'Product'  => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/products/Product',

            'data-qui-option-show-price' => true,
            'data-qui-option-available'  => true
        ]);

        $this->addCSSClass('quiqqer-products-product');
        $this->addCSSFile(\dirname(__FILE__).'/Product.css');

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
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Product = $this->getAttribute('Product');
        $Gallery = new QUI\Gallery\Controls\Slider();
        $fields  = [];
        $Calc    = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());

        $typeDefaultProduct = ($Product->getType() === QUI\ERP\Products\Product\Product::class);
        $typeVariantParent  = ($Product->getType() === QUI\ERP\Products\Product\Types\VariantParent::class);
        $typeVariantChild   = ($Product->getType() === QUI\ERP\Products\Product\Types\VariantChild::class);

        if ($typeVariantParent) {
            /* @var $Product QUI\ERP\Products\Product\Types\VariantParent */
            $this->setAttributes([
                'data-qui-option-show-price' => false,
                'data-qui-option-available'  => false
            ]);

            // use default variant, if a default variant exists
            if ($Product->getDefaultVariantId()) {
                try {
                    $Product = $Product->getDefaultVariant();

                    $this->setAttributes([
                        'data-qui-option-show-price' => true,
                        'data-qui-option-available'  => true
                    ]);

                    $typeVariantChild  = true;
                    $typeVariantParent = false;
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception);
                }
            }
        }

        $User = QUI::getUserBySession();

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            $View   = $Product->getView();
            $Unique = $Product->createUniqueProduct($User);

            try {
                $Price = $Calc->getProductPrice($Unique);
            } catch (QUI\Exception $Exception) {
                $Price = null;
                QUI\System\Log::writeException($Exception);
            }
        } else {
            $View  = $Product;
            $Price = $Product->getPrice($User);
        }

        if ($typeVariantParent) {
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
            $images = $Product->getImages();

            foreach ($images as $Image) {
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
            'Price'       => $Price,
            'withVatText' => true,
            'Calc'        => $Calc,
            'vatArray'    => $vatArray
        ]);

        // retail price (UVP)
        $PriceRetailDisplay = false;

        if ($Product->getFieldValue('FIELD_PRICE_RETAIL')) {
            $PriceRetailDisplay = new QUI\ERP\Products\Controls\Price([
                'Price'       => new QUI\ERP\Money\Price(
                    $Product->getFieldValue('FIELD_PRICE_RETAIL'),
                    QUI\ERP\Currency\Handler::getDefaultCurrency()
                ),
                'withVatText' => false
            ]);
        }

        // offer price (Angebotspreis)
        $PriceOldDisplay = false;

        if ($View->hasOfferPrice()) {
            $PriceOldDisplay = new QUI\ERP\Products\Controls\Price([
                'Price'       => new QUI\ERP\Money\Price(
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
            $Product->getFieldsByType(Fields::TYPE_TEXTAREA_MULTI_LANG)
        );

        /* @var $Field QUI\ERP\Products\Field\Types\Folder */
        foreach ($fieldsList as $Field) {
            if ($Field->getId() == Fields::FIELD_FOLDER || $Field->getId() == Fields::FIELD_CONTENT) {
                continue;
            }

            if (!$Field->hasViewPermission()) {
                continue;
            }

            $detailFields[] = $Field;
        }

        // product fields
        $productFields    = [];
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

            $Slider = new ChildrenSlider();
            $Slider->addProducts($Field->getValue());

            $productFields[] = [
                'Field'  => $Field,
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
                'Field'   => $Product->getField(Fields::FIELD_FOLDER),
                'Product' => $Product,
                'files'   => true,
                'images'  => false
            ]);
        }

        if ($typeVariantParent || $typeVariantChild) {
            QUI\ERP\Products\Utils\Products::setAvailableFieldOptions($Product);
        }

        $Engine->assign([
            'Product'                => $View,
            'Gallery'                => $Gallery,
            'Files'                  => $Files,
            'fields'                 => FieldUtils::sortFields($fields),
            'details'                => FieldUtils::sortFields($details),
            'detailFields'           => FieldUtils::sortFields($detailFields),
            'productAttributeList'   => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST),
            'productAttributeGroups' => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_GROUPS),
            'Price'                  => $Price,
            'PriceDisplay'           => $PriceDisplay,
            'PriceRetailDisplay'     => $PriceRetailDisplay,
            'priceRetailValue'       => $Product->getFieldValue('FIELD_PRICE_RETAIL'),
            'PriceOldDisplay'        => $PriceOldDisplay,
            'VisitedProducts'        => new VisitedProducts(),
            'MediaUtils'             => new QUI\Projects\Media\Utils(),
            'Site'                   => $this->getSite()
        ]);

        // button list
        $Buttons = new Collection([]);

        QUI::getEvents()->fireEvent(
            'quiqqerProductsProductViewButtons',
            [$View, &$Buttons, $this]
        );

        $Engine->assign('Buttons', $Buttons);

        $Engine->assign(
            'buttonsHtml',
            $Engine->fetch(\dirname(__FILE__).'/Product.Buttons.html')
        );

        // normal product
        if (!$typeVariantParent && !$typeVariantChild) {
            return $Engine->fetch(\dirname(__FILE__).'/Product.html');
        }


        // variant product
        $this->setAttributes([
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/products/ProductVariant'
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/ProductVariant.html');
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
