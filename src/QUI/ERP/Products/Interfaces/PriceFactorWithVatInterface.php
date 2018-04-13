<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface
 */

namespace QUI\ERP\Products\Interfaces;

/**
 * Interface PriceFactorWithVat
 * @package QUI\ERP\Products\Interfaces
 */
interface PriceFactorWithVatInterface extends PriceFactorInterface
{
    /**
     * @return \QUI\ERP\Tax\TaxType
     */
    public function getVatType();
}
