<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Tax
 */

namespace QUI\ERP\Products\Field\Types;

/**
 * Class FloatType
 * @package QUI\ERP\Products\Field
 */
class Tax extends Vat
{
    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Tax';
    }

    /**
     * Return the frontend view
     */
    public function getFrontendView(): VatFrontendView
    {
        return new TaxFrontendView($this->getFieldDataForView());
    }
}
