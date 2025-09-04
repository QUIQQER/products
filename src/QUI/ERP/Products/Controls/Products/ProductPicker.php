<?php

namespace QUI\ERP\Products\Controls\Products;

use QUI;

class ProductPicker extends QUI\Control
{
    public function __construct($params = [])
    {
        parent::__construct($params);

        $this->setAttributes([
            'nodeName' => 'section',
            'class' => 'quiqqer-products-controls-product-productPicker',
            'sheetOptionsStyle' => 'select', // select, radio
            'sheetOptions' => [],
            'sheets' => [],
            'showProductDetails' => true
        ]);

        $this->setJavaScriptControl(
            'package/quiqqer/products/bin/controls/frontend/products/ProductPicker'
        );

        $this->addCSSFile(dirname(__FILE__) . '/ProductPicker.css');
    }

    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $sheetOptions = $this->getAttribute('sheetOptions');
        $sheets = $this->getAttribute('sheets');

        if (!is_array($sheetOptions)) {
            $sheetOptions = [];
        }

        if (!is_array($sheets)) {
            $sheets = [];
        }

        $SessionUser = QUI::getUserBySession();

        // products
        foreach ($sheets as $k => $sheet) {
            foreach ($sheet['options'] as $interval => $productId) {
                $Product = QUI\ERP\Products\Handler\Products::getProduct($productId);
                $Price = $Product->getPrice($SessionUser);

                $sheets[$k]['options'][$interval] = [
                    'id' => $productId,
                    'Product' => $Product->getView(),
                    'Price' => $Price
                ];
            }
        }

        $Engine->assign([
            'this' => $this,
            'sheetOptionsStyle' => $this->getAttribute('sheetOptionsStyle'),
            'sheetOptions' => $sheetOptions,
            'sheets' => $sheets,
            'showProductDetails' => $this->getAttribute('showProductDetails')
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ProductPicker.html');
    }
}
