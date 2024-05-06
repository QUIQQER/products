<?php

/**
 * This file contains QUI\ERP\Products\Products\VisitedProducts
 */

namespace QUI\ERP\Products\Controls\Products;

use Exception;
use QUI;

/**
 * Class VisitedProducts
 */
class VisitedProducts extends QUI\Control
{
    /**
     * @var null|ChildrenSlider
     */
    protected $Slider = null;

    /**
     * ChildrenSlider constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setAttribute(
            'data-qui',
            'package/quiqqer/products/bin/controls/frontend/products/VisitedProducts'
        );

        $this->addCSSClass('quiqqer-products-control-visitedProducts');
        $this->addCSSFile(\dirname(__FILE__) . '/VisitedProducts.css');

        $this->Slider = new ChildrenSlider();
        $this->Slider->setAttribute('height', 350);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getBody(): string
    {
        return $this->Slider->create();
    }

    /**
     * Add a product to the children slider
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface|integer $Product
     */
    public function addProduct(QUI\ERP\Products\Interfaces\ProductInterface|int $Product): void
    {
        $this->Slider->addProduct($Product);
    }

    /**
     * Add multiple products to the children slider
     *
     * @param array $products
     */
    public function addProducts($products)
    {
        $this->Slider->addProducts($products);
    }

    /**
     * Return the inner children slider object
     *
     * @return ChildrenSlider
     */
    public function getSlider()
    {
        return $this->Slider;
    }
}
