<?php

namespace QUI\ERP\Products\Field;

use QUI\ERP\Products\Field\Types\Price;

/**
 * Interface for all price field providers (other packages).
 */
interface PriceFieldsProviderInterface
{
    /**
     * @return string[]
     */
    public static function getPriceFieldTypes(): array;
}
