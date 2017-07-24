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
            'data-qui'    => 'package/quiqqer/products/bin/controls/frontend/Price',
            'Price'       => null,
            'withVatText' => false,
            'Calc'        => false
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

        /* @var $Price QUI\ERP\Money\Price */
        $Price = $this->getAttribute('Price');

        if (!$Price) {
            return '';
        }

        $this->setAttribute('data-qui-options-price', $Price->getNetto());
        $this->setAttribute('data-qui-options-currency', $Price->getCurrency()->getCode());

        if ($this->getAttribute('withVatText') === false) {
            return $Price->getDisplayPrice();
        }


        $vatArray = $this->getAttribute('vatArray');

        if ($vatArray && is_array($vatArray) && isset($vatArray['text'])) {
            $vatText = $vatArray['text'];
        } else {
            $Calc = $this->getAttribute('Calc');

            if (!$Calc) {
                $Calc = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());
            }

            $vatText = $Calc->getVatTextByUser();
        }

        $result = '<span class="qui-products-price-display-value">';
        $result .= $Price->getDisplayPrice();
        $result .= '</span>';
        $result .= '<span class="qui-products-price-display-vat">';
        $result .= $vatText;
        $result .= '</span>';

        return $result;
    }
}
