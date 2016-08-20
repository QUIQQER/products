<?php

/**
 * This file contains QUI\ERP\Products\Products\Product
 */
namespace QUI\ERP\Products\Controls\Products;

use QUI;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class ProductEdit
 *
 * Only for product editing
 */
class ProductEdit extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'Product' => false
        ));

        $this->addCSSClass('quiqqer-products-productEdit');
        $this->addCSSFile(dirname(__FILE__) . '/ProductEdit.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        /* @var $Product QUI\ERP\Products\Product\Product */
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Product = $this->getAttribute('Product');
        $Calc    = QUI\ERP\Products\Utils\Calc::getInstance(QUI::getUserBySession());

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            $View  = $Product->getView();
            $Price = $Calc->getProductPrice($Product->createUniqueProduct($Calc));
        } else {
            $View  = $Product;
            $Price = $Product->getPrice();
        }

        $Engine->assign(array(
            'Product' => $View,
            'Price'   => $Price,

            'productAttributeList' => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST)
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductEdit.html');
    }
}
