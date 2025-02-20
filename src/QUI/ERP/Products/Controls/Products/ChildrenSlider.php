<?php

/**
 * This file contains QUI\ERP\Products\Controls\Products\ChildrenSlider
 */

namespace QUI\ERP\Products\Controls\Products;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

use function dirname;
use function is_numeric;

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
    protected array $products = [];

    /**
     * ChildrenSlider constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // default options
        $this->setAttributes([
            'showPrices' => true,
            'buttonAction' => 'addToBasket' // addToBasket / showProduct
        ]);

        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__) . '/ChildrenSlider.css'
        );
    }

    /**
     * @return string
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

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
            'this' => $this,
            'products' => $products
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ChildrenSlider.html');
    }

    /**
     * Add a product to the children slider
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface|integer $Product
     */
    public function addProduct(QUI\ERP\Products\Interfaces\ProductInterface | int $Product): void
    {
        if (is_numeric($Product)) {
            try {
                $this->products[] = Products::getProduct($Product)->getView();
            } catch (QUI\Exception) {
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
    public function addProducts(array $products): void
    {
        foreach ($products as $Product) {
            $this->addProduct($Product);
        }
    }

    /**
     * Get retail price object
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface $Product
     * @return QUI\ERP\Products\Controls\Price | null
     *
     * @throws QUI\Exception
     */
    public function getRetailPrice(
        QUI\ERP\Products\Interfaces\ProductInterface $Product
    ): ?QUI\ERP\Products\Controls\Price {
        if ($this->getAttribute('hideRetailPrice')) {
            return null;
        }

        $CrossedOutPrice = null;
        $Price = $Product->getPrice();

        try {
            // Offer price (Angebotspreis) - it has higher priority than retail price
            if ($Product->hasOfferPrice()) {
                $CrossedOutPrice = new QUI\ERP\Products\Controls\Price([
                    'Price' => new QUI\ERP\Money\Price(
                        $Product->getOriginalPrice()->getValue(),
                        QUI\ERP\Currency\Handler::getDefaultCurrency()
                    ),
                    'withVatText' => false
                ]);
            } else {
                // retail price (UVP)
                if (
                    $Product->getFieldValue(Fields::FIELD_PRICE_RETAIL)
                    && method_exists($Product, 'getCalculatedPrice')
                ) {
                    $PriceRetail = $Product->getCalculatedPrice(Fields::FIELD_PRICE_RETAIL)->getPrice();

                    if ($Price->getPrice() < $PriceRetail->getPrice()) {
                        $CrossedOutPrice = new QUI\ERP\Products\Controls\Price([
                            'Price' => $PriceRetail,
                            'withVatText' => false
                        ]);
                    }
                }
            }

            return $CrossedOutPrice;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return null;
    }
}
