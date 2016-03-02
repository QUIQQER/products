<?php

/**
 * This file contains QUI\ERP\Products\Controls\Watchlist
 */
namespace QUI\ERP\Products\Controls\Watchlist;

use QUI;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Watchlist
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
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/watchlist/Button'
        ));

        $this->addCSSClass('qui-products-watchlist-button');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Locale = QUI::getLocale();
        $text   = $Locale->get('quiqqer/products', 'control.products-watchlist-button-text');

        return '
            <span class="qui-products-watchlist-button-icon fa fa-spinner fa-spin"></span>
            <span class="qui-products-watchlist-button-text">' . $text . '</span>
        ';
    }
}
