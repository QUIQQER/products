<?php

namespace QUI\ERP\Products\Field;

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
     *     basis         // from which price should calculated - netto or calculated
     *     value
     *     calculation
     *     valueText     // text for value presentation (optional)
     * );
     *
     * @param \QUI\Locale|null $Locale
     * @return array
     */
    public function getCalculationData($Locale = null);
}
