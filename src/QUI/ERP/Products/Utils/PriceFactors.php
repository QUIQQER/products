<?php

/**
 * This file contains QUI\ERP\Products\Utils
 */

namespace QUI\ERP\Products\Utils;

use QUI;

use function array_merge;
use function json_encode;
use function usort;

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
     * @var QUI\ERP\Products\Interfaces\PriceFactorInterface[]
     */
    protected array $list = [];

    /**
     * internal list of price factors
     * be sorted at the beginning
     *
     * @var array
     */
    protected array $listBeginning = [];

    /**
     * internal list of price factors
     * be sorted at the end
     *
     * @var array
     */
    protected array $listEnd = [];

    /**
     * own internal currency
     *
     * @var QUI\ERP\Currency\Currency
     */
    protected QUI\ERP\Currency\Currency $Currency;

    /**
     * PriceFactors constructor.
     */
    public function __construct()
    {
        $this->Currency = QUI\ERP\Defaults::getCurrency();
    }

    /**
     * Return the number of the price factors
     *
     * @return int
     */
    public function count(): int
    {
        $count = 0;
        $count = $count + \count($this->listBeginning);
        $count = $count + \count($this->list);
        $count = $count + \count($this->listEnd);

        return $count;
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
     * @param QUI\ERP\Currency\Currency $Currency
     * @return void
     */
    public function setCurrency(QUI\ERP\Currency\Currency $Currency)
    {
        $this->Currency = $Currency;
    }

    /**
     * Return all price factors prioritized
     * and with its position (begin, middle, end)
     *
     * @return QUI\ERP\Products\Interfaces\PriceFactorInterface[]
     */
    public function sort(): array
    {
        $sort = function ($a, $b) {
            /* @var PriceFactor $a */
            /* @var PriceFactor $b */
            if ($a->getPriority() == $b->getPriority()) {
                return \strnatcmp($a->getTitle(), $b->getTitle());
            }

            return $a->getPriority() > $b->getPriority() ? 1 : -1;
        };

        usort($this->listBeginning, $sort);
        usort($this->list, $sort);
        usort($this->listEnd, $sort);

        return array_merge($this->listBeginning, $this->list, $this->listEnd);
    }

    /**
     * Clear the price factors
     */
    public function clear()
    {
        $this->listBeginning = [];
        $this->list = [];
        $this->listEnd = [];
    }

    /**
     * @return QUI\ERP\Products\Interfaces\PriceFactorInterface[]
     */
    public function getFactors(): array
    {
        return $this->sort();
    }

    /**
     * Return the price factor list as an array
     * This can be imported
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'beginning' => [],
            'middle' => [],
            'end' => []
        ];

        foreach ($this->listBeginning as $PriceFactor) {
            $result['beginning'][] = $PriceFactor->toArray();
        }

        foreach ($this->list as $PriceFactor) {
            $result['middle'][] = $PriceFactor->toArray();
        }

        foreach ($this->listEnd as $PriceFactor) {
            $result['end'][] = $PriceFactor->toArray();
        }

        return $result;
    }

    /**
     * Return the list in json notation
     *
     * @return string
     */
    public function toJSON(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Imports a price factor array list
     *
     * @param array $list
     */
    public function importList(array $list)
    {
        if (
            !isset($list['beginning'])
            && !isset($list['middle'])
            && !isset($list['end'])
        ) {
            return;
        }

        $beginning = [];
        $middle = [];
        $end = [];

        if (isset($list['beginning'])) {
            $beginning = $list['beginning'];
        }

        if (isset($list['middle'])) {
            $middle = $list['middle'];
        }

        if (isset($list['end'])) {
            $end = $list['end'];
        }

        $getFactor = function ($attributes) {
            if (isset($attributes['class']) && \class_exists($attributes['class'])) {
                return new $attributes['class']($attributes);
            }

            return new PriceFactor($attributes);
        };

        foreach ($beginning as $priceFactor) {
            $this->listBeginning[] = $getFactor($priceFactor);
        }

        foreach ($middle as $priceFactor) {
            $this->list[] = $getFactor($priceFactor);
        }

        foreach ($end as $priceFactor) {
            $this->listEnd[] = $getFactor($priceFactor);
        }

        $this->sort();
    }

    /**
     * Return this price factor list to a none changeable erp price factor list
     *
     * @return QUI\ERP\Accounting\PriceFactors\FactorList
     *
     * @throws QUI\ERP\Exception
     */
    public function toErpPriceFactorList(): QUI\ERP\Accounting\PriceFactors\FactorList
    {
        $list = [];
        $sorted = $this->sort();

        foreach ($sorted as $PriceFactor) {
            $PriceFactor->setCurrency($this->Currency->getCode());
            $list[] = $PriceFactor->toErpPriceFactor()->toArray();
        }

        return new QUI\ERP\Accounting\PriceFactors\FactorList($list);
    }
}
