<?php

/**
 * This file contains QUI\ERP\Products\Product\ProductListBackendView
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class ProductListBackendView.
 * FrontendView for a product list
 *
 * @package QUI\ERP\Products\Product
 */
class ProductListBackendView
{
    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var ProductList
     */
    protected $ProductList = null;

    /**
     * ProductListView constructor.
     * @param ProductList $ProductList
     */
    public function __construct(ProductList $ProductList)
    {
        $this->ProductList = $ProductList;
        $this->parse();
    }

    /**
     * Return the ProductListView as an array
     *
     * @return array
     */
    protected function parse()
    {
        $list     = $this->ProductList->toArray();
        $products = $this->ProductList->getProducts();
        $User     = $this->ProductList->getUser();
        $isNetto  = QUI\ERP\Products\Utils\User::isNettoUser($User);

        $Locale   = $this->ProductList->getUser()->getLocale();
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Currency->setLocale($Locale);

        $productList = array();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            $attributes   = $Product->getAttributes();
            $fields       = $Product->getFields();
            $PriceFactors = $Product->getPriceFactors();

            $product = array(
                'fields'   => array(),
                'vatArray' => array()
            );

            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            foreach ($fields as $Field) {
                if ($Field->isPublic()) {
                    $product['fields'][] = $Field->getAttributes();
                }
            }

            // format
            $product['price']      = $Currency->format($attributes['calculated_price']);
            $product['sum']        = $Currency->format($attributes['calculated_sum']);
            $product['nettoSum']   = $Currency->format($attributes['calculated_nettoSum']);
            $product['basisPrice'] = $Currency->format($attributes['calculated_basisPrice']);

            $product['id']          = $attributes['id'];
            $product['category']    = $attributes['category'];
            $product['title']       = $attributes['title'];
            $product['description'] = $attributes['description'];
            $product['image']       = $attributes['image'];
            $product['quantity']    = $attributes['quantity'];
            $product['attributes']  = array();

            foreach ($attributes['calculated_vatArray'] as $key => $entry) {
                $sum = $entry['sum'];

                if ($sum == 0) {
                    $sum = '';
                } else {
                    $sum = $Currency->format($entry['sum']);
                }

                $product['vatArray'][$key]['sum'] = $sum;
            }

            /* @var QUI\ERP\Products\Utils\PriceFactor $Factor */
            foreach ($PriceFactors->sort() as $Factor) {
                if (!$Factor->isVisible()) {
                    continue;
                }

                $product['attributes'][] = array(
                    'title' => $Factor->getTitle(),
                    'value' => $isNetto ? $Factor->getNettoSumFormatted() : $Factor->getBruttoSumFormatted()
                );
            }

            $productList[] = $product;
        }

        // result
        $result = array(
            'attributes' => array(),
            'vat'        => array()
        );

        foreach ($list['vatArray'] as $key => $entry) {
            $result['vat'][] = array(
                'text'  => $list['vatText'][$key],
                'value' => $Currency->format($entry['sum'])
            );
        }

        /* @var $Factor QUI\ERP\Products\Utils\PriceFactor */
        foreach ($this->ProductList->getPriceFactors()->sort() as $Factor) {
            if (!$Factor->isVisible()) {
                continue;
            }

            $result['attributes'][] = array(
                'title' => $Factor->getTitle(),
                'value' => $isNetto ? $Factor->getNettoSumFormatted() : $Factor->getBruttoSumFormatted()
            );
        }

        $result['products']    = $productList;
        $result['sum']         = $Currency->format($list['sum']);
        $result['subSum']      = $Currency->format($list['subSum']);
        $result['nettoSum']    = $Currency->format($list['nettoSum']);
        $result['nettoSubSum'] = $Currency->format($list['nettoSubSum']);

        $this->data = $result;
    }

    /**
     * Return the ProductListView as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Return the list view as JSON
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * Return the sum
     *
     * @return string
     */
    public function getSum()
    {
        return $this->data['sum'];
    }

    /**
     * Return the subsum
     *
     * @return string
     */
    public function getSubSum()
    {
        return $this->data['subSum'];
    }

    /**
     * Return the netto sum
     *
     * @return string
     */
    public function getNettoSum()
    {
        return $this->data['nettoSum'];
    }

    /**
     * Return the netto sub sum
     *
     * @return string
     */
    public function getNettoSubSum()
    {
        return $this->data['nettoSubSum'];
    }

    /**
     * Return the products
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->data['products'];
    }

    /**
     * Return the generated standard product listing
     *
     * @param bool $css - optional, with inline style, default = true
     * @return string
     */
    public function toHTML($css = true)
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $style  = '';

        if ($css) {
            $style = '<style>';
            $style .= file_get_contents(dirname(__FILE__) . '/ProductListView.css');
            $style .= '</style>';
        }

        $Engine->assign(array(
            'this'  => $this,
            'data'  => $this->data,
            'style' => $style
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductListView.html');
    }
}
