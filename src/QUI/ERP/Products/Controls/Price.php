<?php

/**
 * This file contains QUI\ERP\Products\Controls\Price
 */
namespace QUI\ERP\Products\Controls;

use QUI;

/**
 * Price display
 *
 * @package QUI\ERP\Products\Controls
 */
class Price extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/Price',
            'Price' => null
        ));

        $this->addCSSClass('qui-products-price-display');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        /* @var $Price QUI\ERP\Products\Utils\Price */
        $Price = $this->getAttribute('Price');

        $this->setAttribute('data-qui-options-price', $Price->getNetto());

        return $Price->getDisplayPrice();
    }
}
