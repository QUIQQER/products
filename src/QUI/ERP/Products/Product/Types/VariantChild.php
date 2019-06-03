<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantChild
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\VariantGenerating;

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

    /**
     * VariantChild constructor.
     *
     * @param $pid
     * @param array $product
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function __construct($pid, $product = [])
    {
        parent::__construct($pid, $product);

        // fields values
    }

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

    /**
     * Return a variant children by its variant field hash
     * getVariantByVariantHash will be executed at the parent product
     *
     * @param string $hash
     * @return QUI\ERP\Products\Product\Types\AbstractType
     *
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function getVariantByVariantHash($hash)
    {
        return $this->getParent()->getVariantByVariantHash($hash);
    }

    /**
     * Generate a variant hash for this variant child
     * The variant hash depends on the used fields
     *
     * hash = ;fieldID:fieldValue;fieldID:fieldValue;fieldID:fieldValue;
     *
     * @return string
     */
    public function generateVariantHash()
    {
        $Parent = $this->getParent();
        $fields = VariantGenerating::getInstance()->getFieldsForGeneration($Parent);

        $hashFields = [];

        foreach ($fields as $Field) {
            try {
                $VariantField = $this->getField($Field->getId());
                $hashFields[] = $VariantField;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return QUI\ERP\Products\Utils\Products::generateVariantHashFromFields($hashFields);
    }

    /**
     * @return array<
     */
    public function availableActiveChildFields()
    {
        return $this->getParent()->availableActiveChildFields();
    }

    /**
     * @param array $fieldData
     *
     * @throws QUI\Database\Exception
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    protected function productSave($fieldData)
    {
        parent::productSave($fieldData);

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            ['variantHash' => $this->generateVariantHash()],
            ['id' => $this->getId()]
        );
    }

    /**
     * return all available fields from the variant children
     * this array contains all field ids and field values that are in use in the children
     *
     * @return array
     */
    public function availableChildFields()
    {
        return $this->getParent()->availableChildFields();
    }

    //endregion
}
