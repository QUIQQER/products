<?php

/**
 * This file contains QUI\ERP\Products\Controls\Offer\Button
 */
namespace QUI\ERP\Products\Controls\Offer;

use QUI;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Offer
 */
class Button extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'nodeName' => 'button',
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/offer/Button',
            'Product' => false,
            'disabled' => 'disabled'
        ));

        parent::__construct($attributes);

        $this->addCSSClass('product-offer-button');
        $this->addCSSFile(dirname(__FILE__) . '/Button.css');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        return 'Angebot anfordern';
    }
}
