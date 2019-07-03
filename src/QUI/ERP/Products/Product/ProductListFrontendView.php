<?php

/**
 * This file contains QUI\ERP\Products\Product\ProductListFrontendView
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Field\View;

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
     * @var null|QUI\Locale
     */
    protected $Locale = null;

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected $Currency = null;

    /**
     * ProductListView constructor.
     *
     * @param ProductList $ProductList
     * @param null|QUI\Locale $Locale
     * @throws QUI\Exception
     */
    public function __construct(ProductList $ProductList, $Locale = null)
    {
        $this->ProductList = $ProductList;
        $this->Currency    = $ProductList->getCurrency();
        $this->hidePrice   = $ProductList->isPriceHidden();
        $this->Locale      = $Locale;

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
        $Locale = $this->Locale;

        if ($Locale === null) {
            $Locale = $this->ProductList->getUser()->getLocale();
        }

        $list     = $this->ProductList->toArray($Locale);
        $products = $this->ProductList->getProducts();

        // currency stuff
        $this->Currency->setLocale($Locale);


        $productList = [];
        $hidePrice   = QUI\ERP\Products\Utils\Package::hidePrice();

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            $attributes   = $Product->getAttributes();
            $fields       = $Product->getFields();
            $PriceFactors = $Product->getPriceFactors();

            $product = [
                'fields'        => [],
                'vatArray'      => [],
                'hasOfferPrice' => $Product->hasOfferPrice(),
                'originalPrice' => $this->formatPrice($Product->getOriginalPrice()->getValue())
            ];

            /* @var $Field QUI\ERP\Products\Field\UniqueField */
            foreach ($fields as $Field) {
                if ($Field->isPublic()) {
                    $product['fields'][] = $Field->getView();
                }
            }

            // format
            $product['price']      = $hidePrice ? '' : $this->formatPrice($attributes['calculated_price']);
            $product['sum']        = $hidePrice ? '' : $this->formatPrice($attributes['calculated_sum']);
            $product['nettoSum']   = $hidePrice ? '' : $this->formatPrice($attributes['calculated_nettoSum']);
            $product['basisPrice'] = $hidePrice ? '' : $this->formatPrice($attributes['calculated_basisPrice']);

            $product['id']           = $attributes['id'];
            $product['category']     = $attributes['category'];
            $product['title']        = $attributes['title'];
            $product['description']  = $attributes['description'];
            $product['image']        = $attributes['image'];
            $product['quantity']     = $attributes['quantity'];
            $product['displayPrice'] = true;
            $product['attributes']   = [];

            if (isset($attributes['displayPrice'])) {
                $product['displayPrice'] = $attributes['displayPrice'];
            }

            $calculatedSum = 0;
            $calculatedVat = 0;

            if (isset($attributes['calculated_vatArray']['sum'])) {
                $calculatedSum = $attributes['calculated_vatArray']['sum'];
            }

            if (isset($attributes['calculated_vatArray']['vat'])) {
                $calculatedVat = $attributes['calculated_vatArray']['vat'];
            }

            if ($calculatedSum == 0) {
                $calculatedSum = '';
            } else {
                $calculatedSum = $this->formatPrice($attributes['calculated_vatArray']['sum']);
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
                'value' => $hidePrice ? '' : $this->formatPrice($entry['sum']),
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
        $result['sum']         = $hidePrice ? '' : $this->formatPrice($list['sum']);
        $result['subSum']      = $hidePrice ? '' : $this->formatPrice($list['subSum']);
        $result['nettoSum']    = $hidePrice ? '' : $this->formatPrice($list['nettoSum']);
        $result['nettoSubSum'] = $hidePrice ? '' : $this->formatPrice($list['nettoSubSum']);

        $this->data = $result;
    }

    /**
     * Format the currency
     * - or recalculate it into another currency
     *
     * @param int|float $price
     * @return string
     */
    protected function formatPrice($price)
    {
        return $this->Currency->format($price);
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
        $data = $this->data;

        /* @var $Field View */
        foreach ($data['products'] as $key => $product) {
            foreach ($product['fields'] as $fKey => $Field) {
                $data['products'][$key]['fields'][$fKey] = $Field->getAttributes();
            }
        }

        return $data;
    }

    /**
     * Return the list view as JSON
     *
     * @return string
     */
    public function toJSON()
    {
        return \json_encode($this->toArray());
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
            $style .= \file_get_contents(\dirname(__FILE__).'/ProductListView.css');
            $style .= '</style>';
        }

        $Engine->assign([
            'this'      => $this,
            'data'      => $this->data,
            'style'     => $style,
            'hidePrice' => $this->isPriceHidden(),
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/ProductListView.html');
    }
}
