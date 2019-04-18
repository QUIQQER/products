<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantParent
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Exception;
use QUI\ERP\Products\Utils\Tables;

/**
 * Class Variant
 * - Variant Parent
 *
 * This is a variant product
 *
 * @package QUI\ERP\Products\Product\Types
 */
class VariantParent extends AbstractType
{
    /**
     * @param null $Locale
     * @return mixed
     */
    public static function getTypeTitle($Locale = null)
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/products', 'product.type.variant.parent.title');
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

        return $Locale->get('quiqqer/products', 'product.type.variant.parent.title');
    }

    /**
     * Returns the backend panel control
     */
    public static function getTypeBackendPanel()
    {
        return 'package/quiqqer/products/bin/controls/products/ProductVariant';
    }

    /**
     * Return all variants
     *
     * @return QUI\ERP\Products\Product\Types\VariantChild[]
     *
     * @todo cache
     */
    public function getVariants()
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'parent'],
                'from'   => Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId()
                ]
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        $variants = [];

        foreach ($result as $entry) {
            $productId = (int)$entry['id'];

            try {
                $variants[] = Products::getProduct($productId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $variants;
    }

    /**
     * @throws QUI\Exception
     */
    public function createVariant()
    {
        $Variant = Products::createProduct(
            $this->getCategories(),
            [],
            VariantChild::class
        );

        $Variant->setAttribute('parent', $this->getId());
        $Variant->save();

        return $Variant;
    }

    /**
     * Internal saving method
     *
     * @param array $fieldData - field data
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     * @throws Exception
     */
    protected function productSave($fieldData)
    {
        QUI\Permissions\Permission::checkPermission('product.edit');

        $fields = $this->getAttribute('overwriteableVariantFields');

        if (is_array($fields)) {
            $overwriteable = [];

            // check if fields exists
            foreach ($fields as $fieldId) {
                try {
                    $overwriteable[] = \QUI\ERP\Products\Handler\Fields::getField($fieldId)->getId();
                } catch (\QUI\Exception $Exception) {
                    \QUI\System\Log::writeDebugException($Exception);
                }
            }

            QUI::getDataBase()->update(
                QUI\ERP\Products\Utils\Tables::getProductTableName(),
                ['overwriteableVariantFields' => \json_encode($overwriteable)],
                ['id' => $this->getId()]
            );
        }

        parent::productSave($fieldData);
    }
}
