<?php

/**
 * This file contains QUI\ERP\Products\Field\CustomField
 */

namespace QUI\ERP\Products\Field;

use QUI;
use QUI\ERP\Products\Handler\Products;

/**
 * Class CustomField
 * @package QUI\ERP\Products\Field
 */
abstract class CustomField extends QUI\ERP\Products\Field\Field
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
    public function getAttributes()
    {
        $attributes                = parent::getAttributes();
        $attributes['custom_calc'] = $this->getCalculationData(Products::getLocale());

        if (isset($attributes['custom_calc']['valueText'])) {
            $attributes['valueText'] = $attributes['custom_calc']['valueText'];
        }

        return $attributes;
    }
}
