<?php

/**
 * This file contains QUI\ERP\Products\Price
 */
namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Discount\Discount;

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
     * @var QUI\ERP\Currency\Currency
     */
    protected $Currency;

    /**
     * @var array
     */
    protected $discounts;

    /**
     * User
     * @var bool|QUI\Users\User
     */
    protected $User;

    /**
     * Price constructor.
     *
     * @param float $nettoPrice
     * @param QUI\ERP\Currency\Currency $Currency
     * @param QUI\Users\User|boolean $User - optional, if no user, session user are used
     */
    public function __construct($nettoPrice, QUI\ERP\Currency\Currency $Currency, $User = false)
    {
        $this->netto    = $nettoPrice;
        $this->Currency = $Currency;

        $this->User      = $User;
        $this->discounts = array();
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
     * @todo must be implemented
     */
    public function getPrice()
    {
        $netto = $this->getNetto();
        $price = $netto;


        return $price;
    }

    /**
     * Return the price for the view / displaying
     *
     * @return string
     */
    public function getDisplayPrice()
    {
        return $this->Currency->format($this->getPrice());
    }

    /**
     * Add a discount to the price
     *
     * @param QUI\ERP\Discount\Discount $Discount
     * @throws QUI\Exception
     */
    public function addDiscount(Discount $Discount)
    {
        /* @var $Disc Discount */
        foreach ($this->discounts as $Disc) {
            // der gleiche discount kann nur einmal enthalten sein
            if ($Disc->getId() == $Discount->getId()) {
                return;
            }

            if ($Disc->canCombinedWith($Discount) === false) {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.discount.not.combinable',
                    array(
                        'id1' => $Disc->getId(),
                        'id2' => $Discount->getId()
                    )
                ));
            }
        }

        $this->discounts[] = $Discount;
    }

    /**
     * Return the assigned discounts
     *
     * @return array [Discount, Discount, Discount]
     */
    public function getDiscounts()
    {
        return $this->discounts;
    }
}
