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
            'Product' => false,
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

        // galery
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

        // fields
        $displayedFields = array(
            Fields::FIELD_PRODUCT_NO
        );

        foreach ($displayedFields as $field) {
            try {
                $fields[] = $Product->getField($field);
            } catch (QUI\Exception $Exception) {
            }
        }


        // pricedisplay
        $PriceDisplay = new QUI\ERP\Products\Controls\Price(array(
            'Price' => $Product->getPrice()
        ));


        $Engine->assign(array(
            'Product' => $this->getAttribute('Product'),
            'Gallery' => $Gallery,
            'fields' => $fields,
            'productAttributeList' => $Product->getFieldsByType('ProductAttributeList'),
            'PriceDisplay' => $PriceDisplay,
            'WatchlistButton' => new WatchlistButton(array(
                'Product' => $Product,
                'width' => 'calc(50% - 5px)'
            )),
            'OfferButton' => new OfferButton(array(
                'Product' => $Product,
                'width' => 'calc(50% - 5px)'
            ))
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
