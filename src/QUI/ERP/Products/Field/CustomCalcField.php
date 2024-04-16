<?php

/**
 * This file contains QUI\ERP\Products\Field\CustomField
 */

namespace QUI\ERP\Products\Field;

use QUI;
use QUI\ERP\Products\Handler\Products;

/**
 * Class CustomField
 *
 * Represents a product field that implements its own product price calculation.
 */
abstract class CustomCalcField extends QUI\ERP\Products\Field\Field implements CustomCalcFieldInterface
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
    abstract public function getCalculationData($Locale = null);

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes                = parent::getAttributes();
        $attributes['custom_calc'] = $this->getCalculationData(Products::getLocale());

        if (isset($attributes['custom_calc']['valueText'])) {
            $attributes['valueText'] = $attributes['custom_calc']['valueText'];
        }

        return $attributes;
    }

    /**
     * Is the field a custom field?
     *
     * @return boolean
     */
    public function isCustomField()
    {
        return true;
    }
}
