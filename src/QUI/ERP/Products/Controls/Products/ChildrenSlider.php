<?php

/**
 * This file contains QUI\ERP\Products\Controls\Products
 */

namespace QUI\ERP\Products\Controls\Products;

use QUI;
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
        parent::__construct($attributes);

        $this->addCSSFile(
            \dirname(__FILE__).'/ChildrenSlider.css'
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
            $this->setAttribute('height', 200);
        }

        foreach ($this->products as $Product) {
            /* @var $Product QUI\ERP\Products\Interfaces\ProductInterface */
            $products[] = [
                'Product' => $Product,
                'Price'   => new QUI\ERP\Products\Controls\Price([
                    'Price' => $Product->getPrice()
                ])
            ];
        }

        $Engine->assign([
            'this'     => $this,
            'products' => $products
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/ChildrenSlider.html');
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
}
