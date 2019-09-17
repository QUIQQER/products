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
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'data-qui'    => 'package/quiqqer/products/bin/controls/frontend/Price',
            'Price'       => null,
            'withVatText' => true,
            'Calc'        => false
        ]);

        $this->addCSSClass('qui-products-price-display');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $this->setAttributes([
                'data-qui' => '',
                'Price'    => null
            ]);

            return '';
        }

        /* @var $Price QUI\ERP\Money\Price */
        $Price = $this->getAttribute('Price');

        if (!$Price) {
            return '';
        }

        $this->setAttribute('data-qui-options-price', $Price->value());
        $this->setAttribute('data-qui-options-currency', $Price->getCurrency()->getCode());

        $vatText = '';

        if ($this->getAttribute('withVatText')) {
            $vatArray = $this->getAttribute('vatArray');

            if ($vatArray && \is_array($vatArray) && isset($vatArray['text'])) {
                $vatText = $vatArray['text'];
            } else {
                $Calc = $this->getAttribute('Calc');

                if (!$Calc) {
                    $Calc = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());
                }

                $vatText = $Calc->getVatTextByUser();
            }
        }

        $pricePrefix = '';

        if ($Price->isMinimalPrice()) {
            $pricePrefix = QUI::getLocale()->get('quiqqer/erp', 'price.starting.from');
        }

        $Engine->assign([
            'this'        => $this,
            'pricePrefix' => $pricePrefix,
            'Price'       => $Price,
            'vatText'     => $vatText
        ]);

        return $Engine->fetch(\dirname(__FILE__) . '/Price.html');
    }
}
