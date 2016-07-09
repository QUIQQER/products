<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class ProductList
 * @package QUI\ERP\Products\Product
 */
class ProductList
{
    /**
     * is the product list calculated?
     * @var bool
     */
    protected $calulated = false;

    protected $sum;
    protected $subSum;
    protected $nettoSum;

    protected $displaySum;
    protected $displaySubSum;
    protected $displayNettoSum;

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array
     */
    protected $vatArray = array();

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array()
     */
    protected $vatText;

    /**
     * Prüfen ob EU Vat für den Benutzer in Frage kommt
     * @var
     */
    protected $isEuVat = false;

    /**
     * Wird Brutto oder Netto gerechnet
     * @var bool
     */
    protected $isNetto = true;

    /**
     * Currency information
     * @var array
     */
    protected $currencyData = array(
        'currency_sign' => '',
        'currency_code' => '',
        'user_currency' => '',
        'currency_rate' => ''
    );

    /**
     * @var array
     */
    protected $products = array();

    /**
     * Doublicate entries allowed?
     * Default = false
     * @var bool
     */
    public $duplicate = true;

    /**
     * PriceFactor List
     * @var QUI\ERP\Products\Utils\PriceFactors
     */
    protected $PriceFactors = false;

    /**
     * ProductList constructor.
     *
     * @param array $params - optional, list settings
     */
    public function __construct($params = array())
    {
        if (isset($params['duplicate'])) {
            $this->duplicate = (boolean)$params['duplicate'];
        }

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();
    }

    /**
     * Calculate the prices in the list
     *
     * @return ProductList
     */
    public function calc()
    {
        if ($this->calulated) {
            return $this;
        }

        $self = $this;

        QUI\ERP\Products\Utils\Calc::getInstance()->calcProductList($this, function ($data) use ($self) {
            $self->sum             = $data['sum'];
            $self->subSum          = $data['subSum'];
            $self->nettoSum        = $data['nettoSum'];
            $self->displaySum      = $data['displaySum'];
            $self->displaySubSum   = $data['displaySubSum'];
            $self->displayNettoSum = $data['displayNettoSum'];
            $self->vatArray        = $data['vatArray'];
            $self->vatText         = $data['vatText'];
            $self->isEuVat         = $data['isEuVat'];
            $self->isNetto         = $data['isNetto'];
            $self->currencyData    = $data['currencyData'];

            $self->calulated = true;

        });

        return $this;
    }

    /**
     * Return the length of the list
     *
     * @return int
     */
    public function count()
    {
        return count($this->getProducts());
    }

    /**
     * Return the products
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Reutrn the price factors list (list of price indicators)
     *
     * @return QUI\ERP\Products\Utils\PriceFactors
     */
    public function getPriceFactors()
    {
        return $this->PriceFactors;
    }

    /**
     * Add a product to the list
     * @param QUI\ERP\Products\Interfaces\Product $Product
     */
    public function addProduct(QUI\ERP\Products\Interfaces\Product $Product)
    {
        // only UniqueProduct can be calculated

        /* @var $Product QUI\ERP\Products\Product\Model */
        if ($Product instanceof QUI\ERP\Products\Product\Model) {
            $Product = $Product->createUniqueProduct();
        }

        if (!($Product instanceof UniqueProduct)) {
            return;
        }

        if ($this->duplicate) {
            $this->products[] = $Product;
            return;
        }

        $this->products[$Product->getId()] = $Product;
    }

    /**
     * Clears the list
     */
    public function clear()
    {
        $this->products = array();
    }

    /**
     * Return the products as array list
     *
     * @return array
     */
    public function toArray()
    {
        $this->calc();
        $products = array();

        /* @var $Product Product */
        foreach ($this->products as $Product) {
            $attributes = $Product->getAttributes();
            $fields     = $Product->getFields();

            $attributes['fields'] = array();

            /* @var $Field QUI\ERP\Products\Interfaces\Field */
            foreach ($fields as $Field) {
                $attributes['fields'][] = $Field->getAttributes();
            }

            $products[] = $attributes;
        }

        $result = array(
            'products'        => $products,
            'sum'             => $this->sum,
            'subSum'          => $this->subSum,
            'nettoSum'        => $this->nettoSum,
            'displaySum'      => $this->displaySum,
            'displaySubSum'   => $this->displaySubSum,
            'displayNettoSum' => $this->displayNettoSum,
            'vatArray'        => $this->vatArray,
            'vatText'         => $this->vatText,
            'noEuVat'         => $this->nettoSum,
            'isNetto'         => $this->isNetto,
            'currencyData'    => $this->currencyData
        );

        return $result;
    }

    /**
     * Return the products as json notation
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }
}
