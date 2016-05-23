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
            'Price'    => null
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
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $this->setAttributes(array(
                'data-qui' => '',
                'Price'    => null
            ));

            return '';
        }

        /* @var $Price QUI\ERP\Products\Utils\Price */
        $Price = $this->getAttribute('Price');

        $this->setAttribute('data-qui-options-price', $Price->getNetto());
        $this->setAttribute('data-qui-options-currency', $Price->getCurrency()->getCode());

        return $Price->getDisplayPrice();
    }
}
