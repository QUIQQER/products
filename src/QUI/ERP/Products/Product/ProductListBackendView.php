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
     * @var bool
     */
    protected $hidePrice;

    /**
     * ProductListView constructor.
     * @param ProductList $ProductList
     *
     * @throws QUI\Exception
     */
    public function __construct(ProductList $ProductList)
    {
        $this->ProductList = $ProductList;
        $this->hidePrice   = $ProductList->isPriceHidden();
        $this->parse();
    }

    /**
     * Parse the list to an array
     * set the internal data
     *
     * @throws QUI\Exception
     */
    protected function parse()
    {
        $list     = $this->ProductList->toArray();
        $products = $this->ProductList->getProducts();
        $User     = $this->ProductList->getUser();
        $isNetto  = QUI\ERP\Utils\User::isNettoUser($User);

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
                'vatArray' => array(),
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


            $calculatedSum = $attributes['calculated_vatArray']['sum'];
            $calculatedVat = $attributes['calculated_vatArray']['vat'];

            if ($calculatedSum == 0) {
                $calculatedSum = '';
            } else {
                $calculatedSum = $Currency->format($attributes['calculated_vatArray']['sum']);
            }

            $product['vatArray'][$calculatedVat]['sum'] = $calculatedSum;

            /* @var QUI\ERP\Products\Utils\PriceFactor $Factor */
            foreach ($PriceFactors->sort() as $Factor) {
                if (!$Factor->isVisible()) {
                    continue;
                }

                $product['attributes'][] = array(
                    'title'     => $Factor->getTitle(),
                    'value'     => $isNetto ? $Factor->getNettoSumFormatted() : $Factor->getBruttoSumFormatted(),
                    'valueText' => $Factor->getValueText(),
                );
            }

            $productList[] = $product;
        }

        // result
        $result = array(
            'attributes' => array(),
            'vat'        => array(),
        );

        foreach ($list['vatArray'] as $key => $entry) {
            $result['vat'][] = array(
                'text'  => $list['vatText'][$key],
                'value' => $Currency->format($entry['sum']),
            );
        }

        /* @var $Factor QUI\ERP\Products\Utils\PriceFactor */
        foreach ($this->ProductList->getPriceFactors()->sort() as $Factor) {
            if (!$Factor->isVisible()) {
                continue;
            }

            $result['attributes'][] = array(
                'title'     => $Factor->getTitle(),
                'value'     => $isNetto ? $Factor->getNettoSumFormatted() : $Factor->getBruttoSumFormatted(),
                'valueText' => $Factor->getValueText(),
            );
        }

        $result['products']    = $productList;
        $result['sum']         = $Currency->format($list['sum']);
        $result['subSum']      = $Currency->format($list['subSum']);
        $result['nettoSum']    = $Currency->format($list['nettoSum']);
        $result['nettoSubSum'] = $Currency->format($list['nettoSubSum']);

        $this->data = $result;
    }

    //region Price methods

    /**
     * Set the price to hidden
     */
    public function hidePrices()
    {
        $this->hidePrice = true;
    }

    /**
     * Set the price to visible
     */
    public function showPrices()
    {
        $this->hidePrice = false;
    }

    /**
     * Return if prices are hidden or not
     *
     * @return bool|int
     */
    public function isPriceHidden()
    {
        return $this->hidePrice;
    }

    //endregion

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
     *
     * @throws QUI\Exception
     */
    public function toHTML($css = true)
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $style  = '';

        if ($css) {
            $style = '<style>';
            $style .= file_get_contents(dirname(__FILE__).'/ProductListView.css');
            $style .= '</style>';
        }

        $Engine->assign(array(
            'this'      => $this,
            'data'      => $this->data,
            'style'     => $style,
            'hidePrice' => $this->isPriceHidden(),
        ));

        return $Engine->fetch(dirname(__FILE__).'/ProductListView.html');
    }
}
