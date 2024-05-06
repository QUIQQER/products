<?php

/**
 * This file contains QUI\ERP\Products\Controls\ProductList
 */

namespace QUI\ERP\Products\Controls\Products;

use Exception;
use QUI;

use function dirname;
use function explode;

/**
 * Class VisitedProducts
 */
class ProductList extends QUI\Control
{
    /**
     * @var null|ChildrenSlider
     */
    protected ?ChildrenSlider $Slider = null;

    /**
     * ChildrenSlider constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'class' => 'quiqqer-products-control-productsList',
            'currentProductId' => 0,
            'type' => 'slider', // list type: slider, gallery, list
            'productIds' => $this->getAttribute('productIds'),
            'sliderHeight' => 350
        ]);
        parent::__construct($attributes);

        $this->addCSSFile(dirname(__FILE__) . '/ProductsList.css');
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getBody(): string
    {
        $this->Slider = new ChildrenSlider();
        $this->Slider->setAttribute('height', $this->getAttribute('sliderHeight'));
        $this->Slider->setAttribute('data-qui-options-usemobile', true);

        $currentProductId = $this->getAttribute('currentProductId');
        $productIds = $this->getAttribute('productIds');
        $Products = new QUI\ERP\Products\Handler\Products();

        if (is_string($productIds)) {
            $productIds = explode(',', $productIds);
        }


        foreach ($productIds as $productId) {
            if (empty($productId) || !is_numeric($productId)) {
                continue;
            }

            if ($currentProductId == $productId) {
                continue;
            }

            try {
                $Product = $Products->getProduct($productId);
                $this->Slider->addProduct($Product->getViewFrontend());
            } catch (QUI\Exception) {
            }
        }

        return $this->Slider->create();
    }
}
