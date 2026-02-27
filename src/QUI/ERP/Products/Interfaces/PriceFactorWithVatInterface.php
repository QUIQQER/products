<?php

/**
 * This file contains QUI\ERP\Products\Interfaces\PriceFactorWithVatInterface
 */

namespace QUI\ERP\Products\Interfaces;

use QUI\ERP\Tax\TaxType;

/**
 * Interface PriceFactorWithVatInterface
 */
interface PriceFactorWithVatInterface extends PriceFactorInterface
{
    /**
     * @return TaxType
     */
    public function getVatType(): TaxType;
}
