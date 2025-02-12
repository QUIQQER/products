<?php

namespace QUI\ERP\Products\Field;

use QUI\Locale;

/**
 * Interface CustomCalcFieldInterface
 *
 * Represents a product field that implements its own product price calculation.
 */
interface CustomCalcFieldInterface
{
    /**
     * Return the array for the calculation
     *
     * return array(
     *     priority      // the priority of the calculation
     *     basis         // from which price should calculate - netto or calculated
     *     value
     *     calculation
     *     valueText     // text for value presentation (optional)
     * );
     *
     * @param Locale|null $Locale
     * @return array
     */
    public function getCalculationData(null | Locale $Locale = null): array;
}
