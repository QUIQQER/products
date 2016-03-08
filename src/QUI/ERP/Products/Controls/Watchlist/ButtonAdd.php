<?php

/**
 * This file contains QUI\ERP\Products\Controls\Watchlist\ButtonAdd
 */
namespace QUI\ERP\Products\Controls\Watchlist;

use QUI;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Watchlist
 */
class ButtonAdd extends QUI\Control
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
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/watchlist/ButtonAdd',
            'input' => true,
            'Product' => false,
            'disabled' => 'disabled'
        ));

        parent::__construct($attributes);

        $this->addCSSClass('product-watchlist-add');
        $this->addCSSFile(dirname(__FILE__) . '/ButtonAdd.css');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        if ($this->getAttribute('Product')) {
            /* @var $Product QUI\ERP\Products\Product\Product */
            $Product = $this->getAttribute('Product');

            $this->setAttribute('data-pid', $Product->getId());
        }

        $html = '';

        if ($this->getAttribute('input')) {
            $html .= '<input type="number" value="1" title="Anzahl"/>';
        }

        $html .= 'Zur Merkliste';

        return $html;
    }
}
