<?php

/**
 * This file contains QUI\ERP\Products\Utils
 */
namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class PriceFactors
 *
 * Price factors is a list of prices from products
 *
 * @package QUI\ERP\Products\Utils
 */
class PriceFactors
{
    /**
     * internal list of price factors
     *
     * @var array
     */
    protected $list = array();

    /**
     * PriceFactors constructor.
     */
    public function __construct()
    {

    }

    /**
     * Return the number of the price factors
     *
     * @return int
     */
    public function count()
    {
        return count($this->list);
    }

    /**
     * @param PriceFactor $PriceFactor
     */
    public function add(PriceFactor $PriceFactor)
    {
        $this->list[] = $PriceFactor;
    }

    /**
     * Return the price factors prioritized
     *
     * @return array
     */
    public function sort()
    {
        usort($this->list, function ($a, $b) {
            /* @var PriceFactor $a */
            /* @var PriceFactor $b */
            return $a->getPriority() > $b->getPriority();
        });

        return $this->list;
    }
}
