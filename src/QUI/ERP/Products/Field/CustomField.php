<?php

/**
 * This file contains QUI\ERP\Products\Field\CustomField
 */

namespace QUI\ERP\Products\Field;

use QUI\ERP\Products\Field\Field;

/**
 * Class CustomField
 * @package QUI\ERP\Products\Field
 */
abstract class CustomField extends Field
{
    /**
     * Return the array for the calculation
     *
     * return array(
     *     priority      // the priority of the calculation
     *     basis         // from which price should calculated - netto or calculated
     *     value
     *     calculation
     * );
     *
     * @return array
     */
    abstract public function getCalculationData();

    /**
     * @return array
     */
    public function getAttributes()
    {
        $attributes                = parent::getAttributes();
        $attributes['custom_calc'] = $this->getCalculationData();

        return $attributes;
    }
}
