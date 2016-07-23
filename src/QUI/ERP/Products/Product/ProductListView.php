<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;
use QUI\Utils\StringHelper;

/**
 * Class ProductListView
 * FrontendView for a product list
 *
 * @package QUI\ERP\Products\Product
 */
class ProductListView
{
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
    }

    /**
     * Return the ProductListView as an array
     *
     * @return array
     */
    public function toArray()
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

            /* @var $Field QUI\ERP\Products\Interfaces\Field */
            foreach ($fields as $Field) {
                if ($Field->isPublic()) {
                    $product['fields'][] = $Field->getAttributes();
                }
            }

            // format
            $product['price']       = $Currency->format($attributes['calculated_price']);
            $product['sum']         = $Currency->format($attributes['calculated_sum']);
            $product['nettoSum']    = $Currency->format($attributes['calculated_nettoSum']);
            $product['basisPrice']  = $Currency->format($attributes['calculated_basisPrice']);
            $product['category']    = $attributes['category'];
            $product['id']          = $attributes['id'];
            $product['title']       = $attributes['title'];
            $product['description'] = $attributes['description'];
            $product['image']       = $attributes['image'];
            $product['quantity']    = $attributes['quantity'];

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
                'text'  => $list['vatText'][$key] . ': ' . $Currency->format($entry['sum']),
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

        return $result;
    }

    /**
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     *
     * @return string
     */
    public function toHTML()
    {
        return '';
    }
}
