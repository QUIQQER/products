<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantChild
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Interfaces\ProductTypeInterface;

/**
 * Class VariantChild
 * - Variant Child
 *
 * @package QUI\ERP\Products\Product\Types
 */
class VariantChild implements ProductTypeInterface
{
    /**
     * @var QUI\ERP\Products\Product\Model
     */
    protected $Product;

    /**
     * Product constructor.
     * @param QUI\ERP\Products\Product\Model $Product
     */
    public function __construct(QUI\ERP\Products\Product\Model $Product)
    {
        $this->Product = $Product;
    }

    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTitle($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.child.title');
    }

    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getDescription($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.child.title');
    }
}
