<?php

/**
 * This file contains QUI\ERP\Products\Controls\Price
 */

namespace QUI\ERP\Products\Controls;

use QUI;
use QUI\ERP\Currency\Handler;

use function dirname;
use function explode;
use function is_array;
use function round;
use function strlen;
use function strval;

/**
 * Price display
 */
class Price extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/Price',
            'Price' => null,
            'withVatText' => true,
            'Calc' => false
        ]);

        $this->addCSSClass('qui-products-price-display');

        parent::__construct($attributes);
    }

    /**
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $this->setAttributes([
                'data-qui' => '',
                'Price' => null
            ]);

            return '';
        }

        /* @var $Price QUI\ERP\Money\Price */
        $Price = $this->getAttribute('Price');

        if (!$Price) {
            return '';
        }

        if (!$Price->value()) {
            return '';
        }


        $this->setAttribute('data-qui-options-price', $Price->value());
        $this->setAttribute('data-qui-options-currency', $Price->getCurrency()->getCode());

        $vatText = '';

        if ($this->getAttribute('withVatText')) {
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
        }

        $pricePrefix = '';

        if ($Price->isMinimalPrice()) {
            $pricePrefix = QUI::getLocale()->get('quiqqer/erp', 'price.starting.from');
        }

        $displayPrice = $Price->getDisplayPrice();

        // price display
        if ($Price->getCurrency()->getCurrencyType() !== Handler::CURRENCY_TYPE_DEFAULT) {
            $numberAsString = strval($Price->getValue());
            $exploded = explode('.', $numberAsString);
            $numberOfDecimalPlaces = isset($exploded[1]) ? strlen($exploded[1]) : 0;

            if ($numberOfDecimalPlaces > 4) {
                $priceRounded = round($Price->getValue(), 4);
                $PriceDisplay = new QUI\ERP\Money\Price($priceRounded, $Price->getCurrency());
                $displayPrice = '~' . $PriceDisplay->getDisplayPrice();
            }
        }

        $Engine->assign([
            'this' => $this,
            'pricePrefix' => $pricePrefix,
            'Price' => $Price,
            'displayPrice' => $displayPrice,
            'vatText' => $vatText
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Price.html');
    }
}
