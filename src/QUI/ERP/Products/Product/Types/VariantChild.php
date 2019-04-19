<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantChild
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Class VariantChild
 * - Variant Child
 *
 * @package QUI\ERP\Products\Product\Types
 */
class VariantChild extends AbstractType
{
    /**
     * @var VariantParent
     */
    protected $Parent = null;

    //region type stuff

    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeTitle($Locale = null)
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
    public static function getTypeDescription($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.child.title');
    }

    /**
     * @return bool|mixed
     */
    public static function isTypeSelectable()
    {
        return false;
    }

    //endregion

    //region product methods

    /**
     * Return the parent variant product
     *
     * @return VariantParent
     */
    public function getParent()
    {
        if ($this->Parent !== null) {
            return $this->Parent;
        }

        try {
            $this->Parent = Products::getProduct(
                $this->getAttribute('parent')
            );
        } catch (QUI\Exception $Exception) {
        }

        return $this->Parent;
    }

    /**
     * Return the title
     *
     * @param null $Locale
     * @return string
     */
    public function getTitle($Locale = null)
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_TITLE, $Locale);

        if (!empty($result)) {
            return $result;
        }

        return $this->getParent()->getTitle($Locale);
    }

    /**
     * Return the title
     *
     * @param null $Locale
     * @return string
     */
    public function getDescription($Locale = null)
    {
        $result = $this->getLanguageFieldValue(Fields::FIELD_SHORT_DESC, $Locale);

        if (!empty($result)) {
            return $result;
        }

        return $this->getParent()->getDescription($Locale);
    }

    /**
     * @return array|string
     */
    public function getCategories()
    {
        return $this->getParent()->getCategories();
    }

    /**
     * @return QUI\ERP\Products\Category\Category|null
     */
    public function getCategory()
    {
        return $this->getParent()->getCategory();
    }

    //endregion
}
