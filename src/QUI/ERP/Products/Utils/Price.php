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
     * @var string
     */
    protected $decimalSeperator = ',';

    /**
     * @var string
     */
    protected $thousandsSeperator = '.';

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
     * Return the price as array notation
     * @return array
     */
    public function toArray()
    {
        return array(
            'price' => $this->getNetto(),
            'currency' => $this->getCurrency()->getCode(),
            'display' => $this->getDisplayPrice()
        );
    }

    /**
     * Return the netto price
     *
     * @return float
     */
    public function getNetto()
    {
        return $this->validatePrice($this->netto);
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
        $price = $this->validatePrice($netto);

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

    /**
     * Return the currency from the price
     *
     * @return QUI\ERP\Currency\Currency
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * calculation
     */

    /**
     * Validates a price value
     *
     * @param number|string $value
     * @return float
     */
    protected function validatePrice($value)
    {
        if (is_float($value)) {
            return round($value, 4);
        }

        $value = (string)$value;
        $value = preg_replace('#[^\d,.]#i', '', $value);

        if (trim($value) === '') {
            return null;
        }

        $decimal   = mb_strpos($value, $this->decimalSeperator);
        $thousands = mb_strpos($value, $this->thousandsSeperator);

        if ($thousands === false && $decimal === false) {
            return round(floatval($value), 4);
        }

        if ($thousands !== false && $decimal === false) {
            if (mb_substr($value, -4, 1) === $this->thousandsSeperator) {
                $value = str_replace($this->thousandsSeperator, '', $value);
            }
        }

        if ($thousands === false && $decimal !== false) {
            $value = str_replace(
                $this->decimalSeperator,
                '.',
                $value
            );
        }

        if ($thousands !== false && $decimal !== false) {
            $value = str_replace($this->thousandsSeperator, '', $value);
            $value = str_replace($this->decimalSeperator, '.', $value);
        }

        return round(floatval($value), 4);
    }
}
