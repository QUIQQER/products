<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\PriceFactorWithVat
 */
namespace QUI\ERP\Products\Interfaces;

/**
 * Interface PriceFactorWithVat
 * @package QUI\ERP\Products\Interfaces
 */
interface PriceFactorWithVat extends PriceFactor
{
    /**
     * @return \QUI\ERP\Tax\TaxType
     */
    public function getVatType();
}
