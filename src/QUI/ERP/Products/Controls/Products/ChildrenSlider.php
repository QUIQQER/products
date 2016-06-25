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
    protected $products = array();

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        if (!$this->getAttribute('height')) {
            $this->setAttribute('height', 200);
        }

        $Engine->assign(array(
            'this'     => $this,
            'products' => $this->products
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ChildrenSlider.html');
    }

    /**
     * Add a product to the children slider
     *
     * @param QUI\ERP\Products\Interfaces\Product|integer $Product
     */
    public function addProduct($Product)
    {
        if (is_numeric($Product)) {
            try {
                $this->products[] = Products::getProduct($Product)->getView();
            } catch (QUI\Exception $Exception) {
            }

            return;
        }

        if ($Product instanceof QUI\ERP\Products\Interfaces\Product) {
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
        if (!is_array($products)) {
            return;
        }

        foreach ($products as $Product) {
            $this->addProduct($Product);
        }
    }
}
