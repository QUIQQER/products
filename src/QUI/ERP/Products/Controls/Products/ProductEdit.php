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
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'Product' => false
        ]);

        $this->addCSSClass('quiqqer-products-productEdit');
        $this->addCSSFile(\dirname(__FILE__).'/ProductEdit.css');

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

        $customFields = [];

        foreach ($Product->getFields() as $Field) {
            if (\method_exists($Field, 'isCustomField') && $Field->isCustomField()) {
                $customFields[] = $Field;
            }
        }

        $Engine->assign([
            'Product' => $View,
            'Price'   => $Price,

            'customFields' => $customFields,

            'productAttributeList' => $View->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST)
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/ProductEdit.html');
    }
}
