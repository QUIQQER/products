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
     * Default price factor type
     */
    const DEFAULT_TYPE = 'PRICE_FACTOR';

    /**
     * internal list of price factors
     *
     * @var array
     */
    protected $list = array();

    /**
     * internal list of price factors
     * be sorted at the beginning
     *
     * @var array
     */
    protected $listBeginning = array();

    /**
     * internal list of price factors
     * be sorted at the end
     *
     * @var array
     */
    protected $listEnd = array();

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
     * Add a price factor
     *
     * @param QUI\ERP\Products\Interfaces\PriceFactorInterface $PriceFactor
     */
    public function add(QUI\ERP\Products\Interfaces\PriceFactorInterface $PriceFactor)
    {
        $this->list[] = $PriceFactor;
    }

    /**
     * Add a price factor to the beginning
     *
     * @param QUI\ERP\Products\Interfaces\PriceFactorInterface $PriceFactor
     */
    public function addToBeginning(QUI\ERP\Products\Interfaces\PriceFactorInterface $PriceFactor)
    {
        $this->listBeginning[] = $PriceFactor;
    }

    /**
     * Add a price factor to the end
     *
     * @param QUI\ERP\Products\Interfaces\PriceFactorInterface $PriceFactor
     */
    public function addToEnd(QUI\ERP\Products\Interfaces\PriceFactorInterface $PriceFactor)
    {
        $this->listEnd[] = $PriceFactor;
    }

    /**
     * Return all price factors prioritized
     * and with its position (begin, middle, end)
     *
     * @return array
     */
    public function sort()
    {
        $sort = function ($a, $b) {
            /* @var PriceFactor $a */
            /* @var PriceFactor $b */
            if ($a->getPriority() == $b->getPriority()) {
                return $a->getTitle() > $b->getTitle();
            }

            return $a->getPriority() > $b->getPriority();
        };

        usort($this->listBeginning, $sort);
        usort($this->list, $sort);
        usort($this->listEnd, $sort);

        return array_merge($this->listBeginning, $this->list, $this->listEnd);
    }
}
