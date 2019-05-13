<?php

/**
 * This file contains QUI\ERP\Products\Product\Types\VariantParent
 */

namespace QUI\ERP\Products\Product\Types;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Exception;
use QUI\ERP\Products\Utils\Tables;
use QUI\ERP\Products\Handler\Fields as FieldHandler;

use QUI\ERP\Products\Field\Types\AttributeGroup;
use QUI\ERP\Products\Field\Types\ProductAttributeList;

/**
 * Class Variant
 * - Variant Parent
 *
 * This is a variant parent product
 *
 * @package QUI\ERP\Products\Product\Types
 *
 * @todo erp overwritable field settings -> Globale default Liste für überschreibbare Felder
 */
class VariantParent extends AbstractType
{
    //region abstract type methods

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

    //endregion

    /**
     * delete the complete product with its children
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI\Permissions\Permission::checkPermission('product.delete');

        // delete children
        $children = $this->getVariants();

        foreach ($children as $Child) {
            try {
                $Child->delete();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // delete itself
        parent::delete();
    }

    /**
     * Return all variants
     *
     * @param array $params - query params
     * @return QUI\ERP\Products\Product\Types\VariantChild[]|integer
     *
     * @todo cache
     */
    public function getVariants($params = [])
    {
        try {
            $query = [
                'select' => ['id', 'parent'],
                'from'   => Tables::getProductTableName(),
                'where'  => [
                    'parent' => $this->getId()
                ]
            ];

            if (isset($params['limit'])) {
                $query['limit'] = $params['limit'];
            }

            if (isset($params['order'])) {
                switch (\mb_strtolower($params['order'])) {
                    case 'active':
                    case 'active asc':
                    case 'active desc':
                    case 'id':
                    case 'id asc':
                    case 'id desc':
                    case 'c_date':
                    case 'c_date asc':
                    case 'c_date desc':
                    case 'e_date':
                    case 'e_date asc':
                    case 'e_date desc':
                        $query['order'] = $params['order'];
                }
            }

            if (isset($params['count'])) {
                unset($query['select']);
                unset($query['limit']);
                unset($query['order']);

                $query['count'] = [
                    'select' => 'id',
                    'as'     => 'count'
                ];
            }

            $result = QUI::getDataBase()->fetch($query);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        if (isset($params['count'])) {
            return (int)$result[0]['count'];
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
     * Generate all variants from the given field combinations
     *
     * @param array $fields - list of field values
     *  [
     *      [
     *          fieldId => 1111,
     *          values => [1,2,3]
     *      ],
     *      [
     *          fieldId => 1111,
     *          values => ['valueId','value','value']
     *      ]
     *  ]
     *
     * @throws QUI\Exception
     */
    public function generateVariants($fields = [])
    {
        if (empty($fields)) {
            return;
        }

        // delete all children and generate new ones
        $children = $this->getVariants();

        foreach ($children as $Child) {
            try {
                $Child->delete();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // generate permutation array
        $list = [];

        foreach ($fields as $entry) {
            $group   = [];
            $fieldId = $entry['fieldId'];
            $values  = $entry['values'];

            foreach ($values as $value) {
                $group[] = ['fieldId' => $fieldId, 'value' => $value];
            }

            if (!empty($group)) {
                $list[] = $group;
            }
        }

        $parentProductNo = $this->getFieldValue(FieldHandler::FIELD_PRODUCT_NO);
        $permutations    = $this->permutations($list);
        $variantNo       = 1;

        // create variant children
        foreach ($permutations as $permutation) {
            // create child
            $Variant = $this->createVariant();

            // set art no
            if (empty($parentProductNo)) {
                $productNo = $variantNo;
            } else {
                $productNo = $parentProductNo.'-'.$variantNo;
            }

            $Variant->getField(FieldHandler::FIELD_PRODUCT_NO)->setValue($productNo);

            $variantNo++;

            // attribute fields
            foreach ($permutation as $entry) {
                $Variant->getField($entry['fieldId'])->setValue($entry['value']);
            }

            $Variant->save();
        }
    }

    /**
     * Generate permutation array (all combinations) from a php array list
     *
     * @param array $lists
     * @return array
     */
    protected function permutations(array $lists)
    {
        $permutations = [];
        $iter         = 0;

        while (true) {
            $num  = $iter++;
            $pick = [];

            foreach ($lists as $l) {
                $r      = $num % \count($l);
                $num    = ($num - $r) / \count($l);
                $pick[] = $l[$r];
            }

            if ($num > 0) {
                break;
            }

            $permutations[] = $pick;
        }

        return $permutations;
    }

    /**
     * Create a new variant
     *
     * @throws QUI\Exception
     */
    public function createVariant()
    {
        $Variant = Products::createProduct(
            $this->getCategories(),
            [],
            VariantChild::class,
            $this->getId()
        );

        $Variant->setAttribute('parent', $this->getId());

        // add AttributeGroups and ProductAttributeList
        $fields = \array_merge(
            $this->getFieldsByType(FieldHandler::TYPE_ATTRIBUTE_LIST),
            $this->getFieldsByType(FieldHandler::TYPE_ATTRIBUTE_GROUPS)
        );

        foreach ($fields as $Field) {
            $Variant->addField($Field);
        }

        $Variant->getField(QUI\ERP\Products\Handler\Fields::FIELD_PRODUCT_NO)->setValue('');
        $Variant->getField(QUI\ERP\Products\Handler\Fields::FIELD_FOLDER)->setValue('');
        $Variant->getField(QUI\ERP\Products\Handler\Fields::FIELD_IMAGE)->setValue('');
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

        $fields = $this->getAttribute('overwritableVariantFields');

        if (\is_array($fields)) {
            $overwritable = [];

            // check if fields exists
            foreach ($fields as $fieldId) {
                try {
                    $overwritable[] = \QUI\ERP\Products\Handler\Fields::getField($fieldId)->getId();
                } catch (\QUI\Exception $Exception) {
                    \QUI\System\Log::writeDebugException($Exception);
                }
            }

            QUI::getDataBase()->update(
                QUI\ERP\Products\Utils\Tables::getProductTableName(),
                ['overwritableVariantFields' => \json_encode($overwritable)],
                ['id' => $this->getId()]
            );
        }

        parent::productSave($fieldData);
    }

    /**
     * Validate the fields and return the field data
     * - workaround for validation
     * -> parent variant can have non valid attribute fields and non valid attribute groups.
     * -> the children have to validate them.
     *
     * @return array
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Exception
     */
    public function validateFields()
    {
        $fields = $this->getAllProductFields();

        foreach ($fields as $Field) {
            if (!($Field instanceof AttributeGroup)
                && !($Field instanceof ProductAttributeList)) {
                continue;
            }

            try {
                $Field->validate($Field->getValue());
            } catch (QUI\Exception $Exception) {
                // if invalid, use the first option
                $options = $Field->getOptions();

                if (empty($options)) {
                    $Field->setValue($options[0]);
                }
            }
        }

        return parent::validateFields();
    }
}
