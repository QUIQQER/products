<?php

/**
 * This file contains QUI\Products\Product\View
 */
namespace QUI\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @package QUI\Products\Product
 */
class View extends QUI\QDOM implements QUI\Products\Interfaces\Product
{
    /**
     * @var Modell
     */
    protected $Product;

    /**
     * View constructor.
     * @param Modell $Product
     */
    public function __construct(Modell $Product)
    {
        $this->Product = $Product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->Product->getId();
    }

    /**
     * @return QUI\Products\Price
     */
    public function getPrice()
    {
        return new QUI\Products\Price(
            $this->getAttribute('price')
        );
    }
}
