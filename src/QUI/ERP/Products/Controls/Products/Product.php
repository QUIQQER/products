<?php

/**
 * This file contains QUI\ERP\Products\Products\Product
 */
namespace QUI\ERP\Products\Controls\Products;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Watchlist\Controls\ButtonAdd as WatchlistButton;
use QUI\ERP\Products\Controls\Offer\Button as OfferButton;

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

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            $View  = $Product->getView();
            $Price = QUI\ERP\Products\Utils\Calc::getInstance()->getProductPrice(
                $Product->createUniqueProduct()
            );

        } else {
            $View  = $Product;
            $Price = $Product->getPrice();
        }

        /* @var $Product QUI\ERP\Products\Product\UniqueProduct */
        $this->setAttribute('data-productid', $View->getId());

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
            $Gallery->setAttribute('folderId', $View->getFieldValue(Fields::FIELD_FOLDER));
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
            if ($Field->getId() == Fields::FIELD_TITLE
                || $Field->getId() == Fields::FIELD_CONTENT
                || $Field->getId() == Fields::FIELD_SHORT_DESC
                || $Field->getId() == Fields::FIELD_PRICE
                || $Field->getId() == Fields::FIELD_TAX
                || $Field->getId() == Fields::FIELD_IMAGE
            ) {
                return false;
            }

            if ($Field->getType() == Fields::TYPE_ATTRIBUTE_LIST
                || $Field->getType() == Fields::TYPE_FOLDER
                || $Field->getType() == Fields::TYPE_IMAGE
            ) {
                return false;
            }

            return $Field->hasViewPermission();
        });


        // pricedisplay
        $PriceDisplay = new QUI\ERP\Products\Controls\Price(array(
            'Price' => $Price
        ));

        // file / image folders
        $mediaFolders = array();
        $mediaFields  = $Product->getFieldsByType(Fields::TYPE_FOLDER);

        /* @var $Field QUI\ERP\Products\Field\Types\Folder */
        foreach ($mediaFields as $Field) {
            if ($Field->getId() == Fields::FIELD_FOLDER) {
                continue;
            }

            if ($Field->getMediaFolder()) {
                $mediaFolders[] = $Field;
            }
        }

        // ChildrenSlider
        $SimilarProductField = $Product->getField(Fields::FIELD_SIMILAR_PRODUCTS);

        if ($SimilarProductField->getValue()) {
            $SimilarProducts = new ChildrenSlider();
            $SimilarProducts->addProducts(
                $Product->getFieldValue(Fields::FIELD_SIMILAR_PRODUCTS)
            );

            $Engine->assign(array(
                'SimilarProducts'     => $SimilarProducts,
                'SimilarProductField' => $SimilarProductField->getView()
            ));
        }


        $Engine->assign(array(
            'Product'              => $View,
            'Gallery'              => $Gallery,
            'fields'               => QUI\ERP\Products\Utils\Fields::sortFields($fields),
            'details'              => QUI\ERP\Products\Utils\Fields::sortFields($details),
            'mediaFolders'         => $mediaFolders,
            'productAttributeList' => $Product->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST),
            'PriceDisplay'         => $PriceDisplay,
            'WatchlistButton'      => new WatchlistButton(array(
                'Product' => $View,
                'width'   => 'calc(50% - 5px)'
            )),
            'OfferButton'          => new OfferButton(array(
                'Product' => $View,
                'width'   => 'calc(50% - 5px)'
            )),
            'MediaUtils'           => new QUI\Projects\Media\Utils()
        ));

        $Engine->assign('buttonsHtml', $Engine->fetch(dirname(__FILE__) . '/Product.Buttons.html'));

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
