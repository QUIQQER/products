<?php

/**
 * This file contains QUI\ERP\Products\Price
 */
namespace QUI\ERP\Products;

use QUI;

/**
 * Class Price
 * @package QUI\ERP\Products\Price
 */
class Price
{
    /**
     * Netto Price
     * @var float
     */
    protected $netto;

    /**
     * Price currency
     * @var string
     */
    protected $currency;

    /**
     * @var array
     */
    protected $Discounts;

    /**
     * User
     * @var bool|QUI\Users\User
     */
    protected $User;

    /**
     * Price constructor.
     *
     * @param float $nettoPrice
     * @param string $currency
     * @param QUI\Users\User|boolean $User - optional, if no user, session user are used
     */
    public function __construct($nettoPrice, $currency, $User = false)
    {
        $this->netto    = $nettoPrice;
        $this->currency = $currency;

        $this->User      = $User;
        $this->Discounts = new QUI\ERP\Discounts\Discounts();
    }

    /**
     * Return the netto price
     *
     * @return float
     */
    public function getNetto()
    {
        return $this->netto;
    }

    /**
     * Return the real price, brutto or netto
     *
     * @return float
     */
    public function getPrice()
    {

    }

    /**
     * @param QUI\ERP\Discounts\Discount $Discount
     */
    public function addDiscount(QUI\ERP\Discounts\Discount $Discount)
    {

    }

    /**
     * @return array|QUI\ERP\Discounts\Discounts
     */
    public function getDiscounts()
    {
        return $this->Discounts;
    }
}
