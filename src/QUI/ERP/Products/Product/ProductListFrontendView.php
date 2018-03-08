<?php

/**
 * This file contains QUI\ERP\Products\Product\ProductListFrontendView
 */

namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class ProductListView
 * FrontendView for a product list
 *
 * @package QUI\ERP\Products\Product
 */
class ProductListFrontendView
{
    /**
     * @var array
     */
    protected $data = [];

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
     *
     * @param ProductList $ProductList
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
     * Set the internal data
     *
     * @throws QUI\Exception
     */
    protected function parse()
    {
        $list     = $this->ProductList->toArray();
        $products = $this->ProductList->getProducts();
//        $User     = $this->ProductList->getUser();
        //$isNetto  = QUI\ERP\Utils\User::isNettoUser($User);

        $Locale   = $this->ProductList->getUser()->getLocale();
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Currency->setLocale($Locale);

        $productList = [];
        $hidePrice   = QUI\ERP\Products\Utils\Package::hidePrice();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            $attributes   = $Product->getAttributes();
            $fields       = $Product->getFields();
            $PriceFactors = $Product->getPriceFactors();

            $product = [
                'fields'   => [],
                'vatArray' => [],
            ];

            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            foreach ($fields as $Field) {
                if ($Field->isPublic()) {
                    $product['fields'][] = $Field->getAttributes();
                }
            }

            // format
            $product['price']      = $hidePrice ? '' : $Currency->format($attributes['calculated_price']);
            $product['sum']        = $hidePrice ? '' : $Currency->format($attributes['calculated_sum']);
            $product['nettoSum']   = $hidePrice ? '' : $Currency->format($attributes['calculated_nettoSum']);
            $product['basisPrice'] = $hidePrice ? '' : $Currency->format($attributes['calculated_basisPrice']);

            $product['id']          = $attributes['id'];
            $product['category']    = $attributes['category'];
            $product['title']       = $attributes['title'];
            $product['description'] = $attributes['description'];
            $product['image']       = $attributes['image'];
            $product['quantity']    = $attributes['quantity'];
            $product['attributes']  = [];


            $calculatedSum = $attributes['calculated_vatArray']['sum'];
            $calculatedVat = $attributes['calculated_vatArray']['vat'];

            if ($calculatedSum == 0) {
                $calculatedSum = '';
            } else {
                $calculatedSum = $Currency->format($attributes['calculated_vatArray']['sum']);
            }

            $product['vatArray'][$calculatedVat]['sum'] = $hidePrice ? '' : $calculatedSum;


            /* @var QUI\ERP\Products\Utils\PriceFactor $Factor */
            foreach ($PriceFactors->sort() as $Factor) {
                if (!$Factor->isVisible()) {
                    continue;
                }

                if ($hidePrice) {
                    $product['attributes'][] = [
                        'title'     => $Factor->getTitle(),
                        'value'     => '',
                        'valueText' => $Factor->getValueText(),
                    ];
                    continue;
                }

                $product['attributes'][] = [
                    'title'     => $Factor->getTitle(),
                    'value'     => $Factor->getSumFormatted(),
                    'valueText' => $Factor->getValueText()
                ];
            }

            $productList[] = $product;
        }

        // result
        $result = [
            'attributes' => [],
            'vat'        => [],
        ];

        foreach ($list['vatArray'] as $key => $entry) {
            $result['vat'][] = [
                'text'  => $list['vatText'][$key],
                'value' => $hidePrice ? '' : $Currency->format($entry['sum']),
            ];
        }

        /* @var $Factor QUI\ERP\Products\Utils\PriceFactor */
        foreach ($this->ProductList->getPriceFactors()->sort() as $Factor) {
            if (!$Factor->isVisible()) {
                continue;
            }

            if ($hidePrice) {
                $product['attributes'][] = [
                    'title'     => $Factor->getTitle(),
                    'value'     => '',
                    'valueText' => $Factor->getValueText(),
                ];
                continue;
            }

            $result['attributes'][] = [
                'title'     => $Factor->getTitle(),
                'value'     => $Factor->getSumFormatted(),
                'valueText' => $Factor->getValueText()
            ];
        }

        $result['products']    = $productList;
        $result['sum']         = $hidePrice ? '' : $Currency->format($list['sum']);
        $result['subSum']      = $hidePrice ? '' : $Currency->format($list['subSum']);
        $result['nettoSum']    = $hidePrice ? '' : $Currency->format($list['nettoSum']);
        $result['nettoSubSum'] = $hidePrice ? '' : $Currency->format($list['nettoSubSum']);

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
     * Return the quantity of products
     *
     * @return int
     */
    public function count()
    {
        return $this->ProductList->count();
    }

    /**
     * Return the sum
     *
     * @return string
     */
    public function getSum()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['sum'];
    }

    /**
     * Return the subsum
     *
     * @return string
     */
    public function getSubSum()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['subSum'];
    }

    /**
     * Return the netto sum
     *
     * @return string
     */
    public function getNettoSum()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['nettoSum'];
    }

    /**
     * Return the netto sub sum
     *
     * @return string
     */
    public function getNettoSubSum()
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

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

        $Engine->assign([
            'this'      => $this,
            'data'      => $this->data,
            'style'     => $style,
            'hidePrice' => $this->isPriceHidden(),
        ]);

        return $Engine->fetch(dirname(__FILE__).'/ProductListView.html');
    }
}
