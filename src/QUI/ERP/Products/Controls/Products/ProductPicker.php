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
            'sheetOptionsStyle' => 'select', // select, radio, button-style1
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

        $sheetOptionsStyle = match($this->getAttribute('sheetOptionsStyle')) {
            'select', 'radio', 'button-style1' => $this->getAttribute('sheetOptionsStyle'),
            default => 'select'
        };

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

        $this->setCustomVariable('countSheets', count($sheets));

        $Engine->assign([
            'this' => $this,
            'sheetOptionsStyle' => $sheetOptionsStyle,
            'sheetOptions' => $sheetOptions,
            'sheets' => $sheets,
            'showProductDetails' => $this->getAttribute('showProductDetails')
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ProductPicker.html');
    }

    /**
     * Set custom css variable to the control as inline style
     * --_q-conf--$name: var(--q-products-productPicker-$name, $value);
     *
     * Example:
     *     --_q-conf--countSheets: var(--q-products-productPicker, 3);
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */

    private function setCustomVariable(string $name, string $value): void
    {
        if (!$name || !$value) {
            return;
        }

        $this->setStyle(
            '--_q-conf--' . $name,
            'var(--q-products-productPicker-' . $name . ', ' . $value . ')'
        );
    }

}
