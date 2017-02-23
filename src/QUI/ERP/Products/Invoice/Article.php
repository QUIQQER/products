<?php

/**
 * This file contains QUI\ERP\Products\Invoice\Article
 */
namespace QUI\ERP\Products\Invoice;

use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product;
use QUI\ERP\Accounting\Invoice\Articles\ArticleInterface;

/**
 * Class Article
 * - Product to Invoice Article
 * - Invoice Article Bridge
 */
class Article implements ArticleInterface
{
    /**
     * @var Product\Product
     */
    protected $Product;

    /**
     * @var int
     */
    protected $quantity = 1;

    /**
     * Article constructor.
     *
     * @param array $attributes
     * @throws Product\Exception
     */
    public function __construct($attributes = array())
    {
        if (!isset($attributes['productId'])) {
            throw new Product\Exception(
                array(
                    'quiqqer/products',
                    'exception.product.not.found',
                    array('productId' => '--')
                ),
                404,
                array('id' => '--')
            );
        }

        $this->Product = Products::getProduct($attributes['productId']);

        if (isset($attributes['quantity'])) {
            $this->quantity = (int)$attributes['quantity'];
        }
    }

    /**
     * @param null $Locale
     * @return string
     */
    public function getTitle($Locale = null)
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param null $Locale
     * @return string
     */
    public function getDescription($Locale = null)
    {
        return $this->Product->getDescription($Locale);
    }

    /**
     * Returns the article quantity
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getSum()
    {
        // TODO: Implement getSum() method.

        return 0;
    }

    public function getUnitPrice()
    {
        // TODO: Implement getUnitPrice() method.

        return 0;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'productId' => $this->Product->getId(),
            'quantity'  => $this->getQuantity(),
            'control'   => 'package/quiqqer/products/bin/controls/invoice/Article'
        );
    }
}
