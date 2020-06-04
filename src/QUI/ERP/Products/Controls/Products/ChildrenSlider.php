<?php

/**
 * This file contains QUI\ERP\Products\Controls\Products\ChildrenSlider
 */

namespace QUI\ERP\Products\Controls\Products;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Class ChildrenSlider
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class ChildrenSlider extends QUI\Bricks\Controls\Children\Slider
{
    /**
     * List of products
     *
     * @var array
     */
    protected $products = [];

    /**
     * ChildrenSlider constructor.
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        // default options
        $this->setAttributes([
            'showPrices'   => true,
            'buttonAction' => 'addToBasket' // addToBasket / showProduct
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(
            \dirname(__FILE__) . '/ChildrenSlider.css'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            return '';
        }

        $products = [];

        if (!$this->getAttribute('height')) {
            $this->setAttribute('height', 300);
        }

        /* @var $Product QUI\ERP\Products\Interfaces\ProductInterface */
        foreach ($this->products as $Product) {
            $details = [
                'Product' => $Product
            ];

            if ($this->getAttribute('showPrices')) {
                $details['Price'] = new QUI\ERP\Products\Controls\Price([
                    'Price' => $Product->getPrice()
                ]);

                $details['RetailPrice'] = $this->getRetailPrice($Product);
            }

            $products[] = $details;
        }

        $Engine->assign([
            'this'     => $this,
            'products' => $products
        ]);

        return $Engine->fetch(\dirname(__FILE__) . '/ChildrenSlider.html');
    }

    /**
     * Add a product to the children slider
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface|integer $Product
     */
    public function addProduct($Product)
    {
        if (\is_numeric($Product)) {
            try {
                $this->products[] = Products::getProduct($Product)->getView();
            } catch (QUI\Exception $Exception) {
            }

            return;
        }

        if ($Product instanceof QUI\ERP\Products\Interfaces\ProductInterface) {
            $this->products[] = $Product;
        }
    }

    /**
     * Add multiple products to the children slider
     *
     * @param array $products
     */
    public function addProducts($products)
    {
        if (!\is_array($products)) {
            return;
        }

        foreach ($products as $Product) {
            $this->addProduct($Product);
        }
    }

    /**
     * Get retail price object
     *
     * @param $Product QUI\ERP\Products\Product\ViewFrontend
     * @return QUI\ERP\Products\Controls\Price | null
     *
     * @throws QUI\Exception
     */
    public function getRetailPrice($Product)
    {
        if ($this->getAttribute('hideRetailPrice')) {
            return null;
        }

        $CrossedOutPrice = null;
        $Price           = $Product->getPrice();

        try {
            // Offer price (Angebotspreis) - it has higher priority than retail price
            if ($Product->hasOfferPrice()) {
                $CrossedOutPrice = new QUI\ERP\Products\Controls\Price([
                    'Price'       => new QUI\ERP\Money\Price(
                        $Product->getOriginalPrice()->getValue(),
                        QUI\ERP\Currency\Handler::getDefaultCurrency()
                    ),
                    'withVatText' => false
                ]);
            } else {
                // retail price (UVP)
                if ($Product->getFieldValue('FIELD_PRICE_RETAIL')) {
                    $PriceRetail = $Product->getCalculatedPrice(Fields::FIELD_PRICE_RETAIL)->getPrice();

                    if ($Price->getPrice() < $PriceRetail->getPrice()) {
                        $CrossedOutPrice = new QUI\ERP\Products\Controls\Price([
                            'Price'       => $PriceRetail,
                            'withVatText' => false
                        ]);
                    }
                }
            }

            return $CrossedOutPrice;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }
}
