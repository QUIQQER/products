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
        $result   = $this->ProductList->toArray();
        $products = $this->ProductList->getProducts();

        $Locale   = $this->ProductList->getUser()->getLocale();
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Currency->setLocale($Locale);

        if (isset($result['products'])) {
            unset($result['products']);
        }

        $productList = array();

        $allowedAttributes = array(
            'calculated*',
            'categories',
            'category',
            'id',
            'active',
            'title',
            'description',
            'image',
            'fields',
            'quantity'
        );

        $filteAttributes = function ($attribute) use ($allowedAttributes) {
            foreach ($allowedAttributes as $allowed) {
                if ($allowed == $attribute) {
                    return true;
                }

                if (StringHelper::match($allowed, $attribute)) {
                    return true;
                }
            }

            return false;
        };

        /* @var $Product UniqueProduct */
        foreach ($products as $Product) {
            $attributes = $Product->getAttributes();
            $fields     = $Product->getFields();

            $attributes['fields'] = array();

            /* @var $Field QUI\ERP\Products\Interfaces\Field */
            foreach ($fields as $Field) {
                if ($Field->isPublic()) {
                    $attributes['fields'][] = $Field->getAttributes();
                }
            }

            // format
            $attributes['calculated_price']    = $Currency->format($attributes['calculated_price']);
            $attributes['calculated_sum']      = $Currency->format($attributes['calculated_sum']);
            $attributes['calculated_nettoSum'] = $Currency->format($attributes['calculated_nettoSum']);

            foreach ($attributes['calculated_vatArray'] as $key => $entry) {
                $sum = $entry['sum'];

                if ($sum == 0) {
                    $sum = '';
                } else {
                    $sum = $Currency->format($entry['sum']);
                }

                $attributes['calculated_vatArray'][$key]['sum'] = $sum;
            }

            foreach ($attributes['calculated_factors'] as $key => $entry) {
                if (!$entry['visible']) {
                    unset($attributes['calculated_factors'][$key]);
                    continue;
                }

                $sum = $entry['sum'];

                if ($sum == 0) {
                    $sum = '';
                } else {
                    $sum = $Currency->format($entry['sum']);
                }

                $attributes['calculated_factors'][$key]['sum'] = $sum;
            }

            $attributes = array_filter(
                $attributes,
                $filteAttributes,
                \ARRAY_FILTER_USE_KEY
            );

            $productList[] = $attributes;
        }


        foreach ($result['vatArray'] as $key => $entry) {
            $result['vatArray'][$key] = $Currency->format($entry['sum']);
        }

        $result['products']    = $productList;
        $result['sum']         = $Currency->format($result['sum']);
        $result['subSum']      = $Currency->format($result['subSum']);
        $result['nettoSum']    = $Currency->format($result['nettoSum']);
        $result['nettoSubSum'] = $Currency->format($result['nettoSubSum']);

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
}
