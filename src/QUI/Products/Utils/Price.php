<?php

/**
 * This file contains QUI\Products\Price
 */
namespace QUI\Products;

use QUI;

/**
 * Class Price
 * @package QUI\Products\Price
 */
class Price
{
    /**
     * Netto Price
     * @var float
     */
    protected $netto;

    /**
     * User
     * @var bool|QUI\Users\User
     */
    protected $User;

    /**
     * Price constructor.
     *
     * @param $nettoPrice
     * @param QUI\Users\User|boolean $User - optional, if no user, session user are used
     */
    public function __construct($nettoPrice, $User = false)
    {
        $this->netto = $nettoPrice;
        $this->User  = $User;
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
}
