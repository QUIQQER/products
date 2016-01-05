<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @package QUI\ERP\Products\Product
 */
class View extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
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
     * @return QUI\ERP\Products\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Products\Price(
            $this->getAttribute('price')
        );
    }
}
