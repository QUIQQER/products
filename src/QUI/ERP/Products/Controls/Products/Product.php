<?php

/**
 * This file contains QUI\ERP\Products\Products\Product
 */
namespace QUI\ERP\Products\Controls\Products;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Watchlist\Controls\ButtonAdd as WatchlistButton;
use QUI\ERP\Watchlist\Controls\ButtonPurchase as PurchaseButton;

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
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'Product'  => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/products/Product'
        ));

        $this->addCSSFile(dirname(__FILE__) . '/Product.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        /* @var $Product QUI\ERP\Products\Product\Product */
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Product = $this->getAttribute('Product');
        $Gallery = new QUI\Gallery\Controls\Slider();
        $fields  = array();
        $Calc    = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            $View   = $Product->getView();
            $Unique = $Product->createUniqueProduct($Calc);

            try {
                $Price = $Calc->getProductPrice($Unique);
            } catch (QUI\Exception $Exception) {
                $Price = null;
                QUI\System\Log::writeException($Exception);
            }
        } else {
            $View  = $Product;
            $Price = $Product->getPrice();
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
        $displayedFields = array(
            Fields::FIELD_PRODUCT_NO
        );

        foreach ($displayedFields as $field) {
            if ($View->getField($field)) {
                $fields[] = $View->getField($field);
            }
        }

        // fields for the details
        $details = array_filter($View->getFields(), function ($Field) {
            /* @var $Field QUI\ERP\Products\Field\View */
            if (!QUI\ERP\Products\Utils\Fields::showFieldInProductDetails($Field)) {
                return false;
            }

            return $Field->hasViewPermission();
        });

        $vatArray = array();

        if (isset($productAttributes['calculated_vatArray'])) {
            $vatArray = $productAttributes['calculated_vatArray'];
        }

        // pricedisplay
        $PriceDisplay = new QUI\ERP\Products\Controls\Price(array(
            'Price'       => $Price,
            'withVatText' => true,
            'Calc'        => $Calc,
            'vatArray'    => $vatArray
        ));

        // file / image folders
        $detailFields = array();

        $fieldsList = array_merge(
            $Product->getFieldsByType(Fields::TYPE_FOLDER),
            $Product->getFieldsByType(Fields::TYPE_TEXTAREA),
            $Product->getFieldsByType(Fields::TYPE_TEXTAREA_MULTI_LANG)
        );

        /* @var $Field QUI\ERP\Products\Field\Types\Folder */
        foreach ($fieldsList as $Field) {
            if ($Field->getId() == Fields::FIELD_FOLDER
                || $Field->getId() == Fields::FIELD_CONTENT
            ) {
                continue;
            }

            if (!$Field->hasViewPermission()) {
                continue;
            }

            $detailFields[] = $Field;
        }

        // product fields
        $productFields = array();

        $productFieldList = array_filter($View->getFields(), function ($Field) {
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

            $productFields[] = array(
                'Field'  => $Field,
                'Slider' => $Slider
            );
        }

        $Engine->assign(array(
            'productFields' => $productFields
        ));

        // Product File List
        $Files = null;

        if (count($Product->getFiles())) {
            $Files = new ProductFieldDetails(array(
                'Field'   => $Product->getField(Fields::FIELD_FOLDER),
                'Product' => $Product,
                'files'   => true,
                'images'  => false
            ));
        }

        $Engine->assign(array(
            'Product'              => $View,
            'Gallery'              => $Gallery,
            'Files'                => $Files,
            'fields'               => QUI\ERP\Products\Utils\Fields::sortFields($fields),
            'details'              => QUI\ERP\Products\Utils\Fields::sortFields($details),
            'detailFields'         => QUI\ERP\Products\Utils\Fields::sortFields($detailFields),
            'productAttributeList' => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST),
            'PriceDisplay'         => $PriceDisplay,
            'VisitedProducts'      => new VisitedProducts(),
            'WatchlistButton'      => new WatchlistButton(array(
                'Product' => $View
            )),
            'OfferButton'          => new PurchaseButton(array(
                'Product' => $View
            )),
            'MediaUtils'           => new QUI\Projects\Media\Utils()
        ));

        $Engine->assign(
            'buttonsHtml',
            $Engine->fetch(dirname(__FILE__) . '/Product.Buttons.html')
        );

        return $Engine->fetch(dirname(__FILE__) . '/Product.html');
    }

    /**
     * @return mixed|QUI\Projects\Site
     */
    protected function getSite()
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
