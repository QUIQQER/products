<?php

/**
 * This file contains QUI\ERP\Products\Utils\Products
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Utils\Fields as FieldUtils;
use QUI\ERP\Products\Product\Exception;

/**
 * Class Products Helper
 *
 * @package QUI\ERP\Products\Utils
 */
class Products
{
    /**
     * Is mixed a product compatible object?
     * looks for:
     * - QUI\ERP\Products\Interfaces\ProductInterface::class
     * - QUI\ERP\Products\Product\Model
     * - QUI\ERP\Products\Product\Product
     *
     * @param $mixed
     * @return bool
     */
    public static function isProduct($mixed)
    {
        if (\get_class($mixed) == QUI\ERP\Products\Product\Model::class) {
            return true;
        }

        if (\get_class($mixed) == QUI\ERP\Products\Product\Product::class) {
            return true;
        }

        if ($mixed instanceof QUI\ERP\Products\Interfaces\ProductInterface) {
            return true;
        }

        return false;
    }

    /**
     * Return the price field from the product for the user
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface|QUI\ERP\Products\Product\Model $Product
     * @param QUI\Interfaces\Users\User|null $User
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public static function getPriceFieldForProduct($Product, $User = null)
    {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        if (!self::isProduct($Product)) {
            throw new QUI\Exception('No Product given');
        }

        $PriceField = $Product->getField(FieldHandler::FIELD_PRICE);
        $priceValue = $PriceField->getValue();
        $Currency   = QUI\ERP\Currency\Handler::getDefaultCurrency();

        // exists more price fields?
        // is user in group filter
        $priceList = \array_merge(
            $Product->getFieldsByType(FieldHandler::TYPE_PRICE),
            $Product->getFieldsByType(FieldHandler::TYPE_PRICE_BY_QUANTITY),
            $Product->getFieldsByType(FieldHandler::TYPE_PRICE_BY_TIMEPERIOD)
        );

        if (empty($priceList)) {
            return new QUI\ERP\Money\Price($PriceField->getValue(), $Currency);
        }

        $priceFields = \array_filter($priceList, function ($Field) use ($User) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */

            // ignore default main price
            if ($Field->getId() == FieldHandler::FIELD_PRICE) {
                return false;
            };

            $options = $Field->getOptions();

            if (!isset($options['groups'])) {
                return true;
            }

            if (isset($options['ignoreForPriceCalculation'])
                && $options['ignoreForPriceCalculation'] == 1
            ) {
                return false;
            }

            $groups = \explode(',', $options['groups']);

            if (empty($groups)) {
                return true;
            }

            foreach ($groups as $gid) {
                if ($User->isInGroup($gid)) {
                    return true;
                }
            }

            return false;
        });

        // use the lowest price?
        foreach ($priceFields as $Field) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */
            $type = 'QUI\\ERP\\Products\\Field\\Types\\'.$Field->getType();

            if (\is_callable([$type, 'onGetPriceFieldForProduct'])) {
                try {
                    $ParentField = FieldHandler::getField($Field->getId());
                    $value       = $ParentField->onGetPriceFieldForProduct($Product, $User);

                    if ($value && $value < $priceValue) {
                        $priceValue = $value;
                    }
                } catch (QUI\Exception $Exception) {
                }

                continue;
            }

            $value = $Field->getValue();

            if ($value === false || $value === '' || $value === null) {
                continue;
            }

            if ($value < $priceValue) {
                $priceValue = $value;
            }
        }

        return new QUI\ERP\Money\Price($priceValue, $Currency);
    }

    /**
     * Return the editable fields for the project
     * editable fields can be changed by the user via the GUI
     *
     * @param null $Product
     * @return array
     */
    public static function getEditableFieldIdsForProduct($Product = null)
    {
        if (!empty($Product) && $Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $Product = $Product->getParent();
        }

        if (!empty($Product) && $Product instanceof QUI\ERP\Products\Product\Product) {
            if ($Product->getAttribute('editableVariantFields')) {
                $editable = $Product->getAttribute('editableVariantFields');

                if (\is_string($editable)) {
                    $editable = \json_decode($editable, true);
                }

                return $editable;
            }
        }

        // global erp editable fields
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $fields = $Config->getSection('editableFields');

        if ($fields) {
            $result = \array_keys($fields);

            return $result;
        }


        $fields = FieldHandler::getFields();
        $result = \array_map(function ($Field) {
            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            return $Field->getId();
        }, $fields);

        return $result;
    }

    /**
     * Return the inherited fields for the project
     *
     * @param null $Product
     * @return array
     */
    public static function getInheritedFieldIdsForProduct($Product = null)
    {
        if (!empty($Product) && $Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $Product = $Product->getParent();
        }

        if (!empty($Product) && $Product instanceof QUI\ERP\Products\Product\Product) {
            if ($Product->getAttribute('inheritedVariantFields')) {
                $inherited = $Product->getAttribute('inheritedVariantFields');

                if (\is_string($inherited)) {
                    $inherited = \json_decode($inherited, true);
                }

                return $inherited;
            }
        }

        // global erp inherited fields
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            return [];
        }

        $fields = $Config->getSection('inheritedFields');

        if ($fields) {
            $result = \array_keys($fields);

            return $result;
        }


        $fields = FieldHandler::getFields();
        $result = \array_map(function ($Field) {
            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            return $Field->getId();
        }, $fields);

        return $result;
    }

    /**
     * Return generate variant hash
     *
     * @param array $fields - could be a field array [Field, Field, Field],
     *                        or could be a field object list ['field-1':2, 'field-1':'value']
     * @return string
     */
    public static function generateVariantHashFromFields($fields)
    {
        $hash = [];

        // get hash values
        foreach ($fields as $Field => $fieldValue) {
            if ($fieldValue instanceof QUI\ERP\Products\Interfaces\FieldInterface) {
                $fieldId    = $fieldValue->getId();
                $fieldValue = $fieldValue->getValue();
            } elseif (\is_string($Field) || \is_numeric($Field)) {
                $fieldId = $Field;
            } else {
                continue;
            }

            // string to hex
            if (!\is_numeric($fieldValue)) {
                $fieldValue = \implode(\unpack("H*", $fieldValue));
            }

            $hash[] = $fieldId.':'.$fieldValue;
        }

        // sort fields
        \usort($hash, function ($a, $b) {
            $aId = (int)\explode(':', $a)[0];
            $bId = (int)\explode(':', $b)[0];

            return $aId - $bId;
        });

        // generate hash
        $generate = ';'.\implode(';', $hash).';';

        return $generate;
    }

    /**
     * @param QUI\ERP\Products\Product\Product $Product
     */
    public static function setAvailableFieldOptions(QUI\ERP\Products\Product\Product $Product)
    {
        if (!($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) &&
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantChild)) {
            return;
        }

        // attribute groups
        $groupList = $Product->getFieldsByType(FieldHandler::TYPE_ATTRIBUTE_GROUPS);

        $available        = $Product->availableActiveChildFields();
        $availableHashes  = $Product->availableActiveFieldHashes();
        $availableEntries = [];

        // parse allowed field values (=options)
        $currentVariantHash = Products::generateVariantHashFromFields($groupList);
        $searchHashes       = FieldUtils::getSearchHashesFromFieldHash($currentVariantHash);

        foreach ($availableHashes as $hash) {
            $hashArray = FieldUtils::parseFieldHashToArray($hash);

            foreach ($hashArray as $fieldId => $fieldValue) {
                if (isset($availableEntries[$fieldId][$fieldValue])) {
                    continue;
                }

                if ($fieldValue === '') {
                    continue;
                }


                if ($currentVariantHash === $hash) {
                    $availableEntries[$fieldId][$fieldValue] = true;
                    continue;
                }

                foreach ($searchHashes as $searchHash) {
                    if (\fnmatch($searchHash, $hash)) {
                        $availableEntries[$fieldId][$fieldValue] = true;
                        break 1;
                    }
                }
            }
        }


        // set field option status
        foreach ($groupList as $Field) {
            /* @var $Field QUI\ERP\Products\Field\Types\AttributeGroup */
            $fieldId = $Field->getId();
            $Field->hideEntries();
            $Field->disableEntries();

            $options = $Field->getOptions();
            $entries = $options['entries'];

            $allowed = $available[$fieldId];
            $allowed = \array_flip($allowed);

            foreach ($entries as $key => $value) {
                $valueId       = $value['valueId'];
                $hashedValueId = false;

                if (!\is_numeric($valueId)) {
                    $hashedValueId = \implode(\unpack("H*", $valueId));

                    if (!isset($allowed[$valueId]) && !isset($allowed[$hashedValueId])) {
                        continue;
                    }
                }

                if (!isset($allowed[$valueId])) {
                    continue;
                }

                $Field->showEntry($key);

                if (isset($availableEntries[$fieldId][$valueId])) {
                    $Field->enableEntry($key);
                    continue;
                }

                if ($hashedValueId && isset($availableEntries[$fieldId][$hashedValueId])) {
                    $Field->enableEntry($key);
                    continue;
                }
            }
        }
    }

    /**
     * @param QUI\ERP\Products\Product\Product $Product
     * @return array
     */
    public static function getJsFieldHashArray(QUI\ERP\Products\Product\Product $Product)
    {
        if (!($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) &&
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantChild)) {
            return [];
        }

        $availableHashes = $Product->availableActiveFieldHashes();
        $result          = [];

        foreach ($availableHashes as $hash) {
            $hashArray = FieldUtils::parseFieldHashToArray($hash);

            foreach ($hashArray as $fieldId => $value) {
                if (!isset($result[$fieldId])) {
                    $result[$fieldId] = [];
                }

                foreach ($hashArray as $fid => $v) {
                    $result[$fieldId][$fid][$v][] = $hash;
                }
            }
        }

        return $result;
    }

    /**
     * Is the product a variant product
     *
     * @param $Product
     * @return bool
     */
    public static function isVariant($Product)
    {
        if ($Product instanceof QUI\ERP\Products\Product\ViewFrontend) {
            $Product = $Product->getProduct();
        }

        if ($Product instanceof QUI\ERP\Products\Product\Types\VariantParent
            || $Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            return true;
        }

        return false;
    }

    /**
     * @param array $urlFieldValue
     * @param integer $categoryId
     * @param integer|false $ignoreProductId - optional
     *
     * @throws Exception
     */
    public static function checkUrlByUrlFieldValue($urlFieldValue, $categoryId, $ignoreProductId = false)
    {
        $urlCacheField = 'F'.FieldHandler::FIELD_URL;
        $table         = QUI\ERP\Products\Utils\Tables::getProductCacheTableName();

        $where = [];
        $binds = [];
        $i     = 0;

        foreach ($urlFieldValue as $lang => $url) {
            if (empty($url)) {
                continue;
            }

            self::checkUrlLength($url, $lang, $categoryId);


            $binds[':lang'.$i]     = $lang;
            $binds[':url'.$i]      = $url;
            $binds[':category'.$i] = '%,'.$categoryId.',%';

            $where[] = "(F19 LIKE :url{$i} AND lang LIKE :lang{$i} AND category LIKE :category{$i})";
            $i++;
        }

        if (empty($where)) {
            return;
        }

        $where = \implode(' OR ', $where);

        $query = "
            SELECT id, {$urlCacheField} 
            FROM {$table}
            WHERE {$where}
        ";

        $PDO       = QUI::getDataBase()->getPDO();
        $Statement = $PDO->prepare($query);

        foreach ($binds as $bind => $value) {
            $Statement->bindValue($bind, $value, \PDO::PARAM_STR);
        }

        $Statement->execute();
        $result = $Statement->fetchAll();

        // no results, all is fine
        if (empty($result)) {
            return;
        }

        foreach ($result as $entry) {
            if ($ignoreProductId && (int)$entry['id'] === $ignoreProductId) {
                continue;
            }

            throw new Exception([
                'quiqqer/products',
                'exception.url.already.exists'
            ]);
        }
    }


    /**
     * Checks the urls length for the product
     *
     * @param string $url
     * @param string $lang
     * @param integer $categoryId
     *
     * @throws Exception
     */
    public static function checkUrlLength($url, $lang, $categoryId)
    {
        try {
            $Category = Categories::getCategory($categoryId);
            $projects = QUI::getProjectManager()->getProjects(true);
        } catch (QUI\Exception $Exception) {
            return;
        }

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            if ($Project->getLang() !== $lang) {
                continue;
            }

            $categoryUrl = $Category->getUrl($Project);

            if (!empty($categoryUrl) && \strlen($categoryUrl.'/'.$url) > 2000) {
                throw new Exception([
                    'quiqqer/products',
                    'exception.url.is.too.long'
                ]);
            }
        }
    }
}
