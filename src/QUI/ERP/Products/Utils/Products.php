<?php

/**
 * This file contains QUI\ERP\Products\Utils\Products
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Interfaces\ProductTypeInterface;
use QUI\ERP\Products\Product\Exception;
use QUI\ERP\Products\Utils\Fields as FieldUtils;

use function array_filter;
use function array_flip;
use function array_map;
use function array_merge;
use function explode;
use function fnmatch;
use function get_class;
use function implode;
use function is_null;
use function is_numeric;
use function is_string;
use function json_decode;
use function method_exists;
use function strlen;
use function trim;
use function unpack;
use function usort;

/**
 * Class Products Helper
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
    public static function isProduct($mixed): bool
    {
        if (get_class($mixed) == QUI\ERP\Products\Product\Model::class) {
            return true;
        }

        if (get_class($mixed) == QUI\ERP\Products\Product\Product::class) {
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
    public static function getPriceFieldForProduct(
        QUI\ERP\Products\Interfaces\ProductInterface | QUI\ERP\Products\Product\Model $Product,
        null | QUI\Interfaces\Users\User $User = null
    ): QUI\ERP\Money\Price {
        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUsers()->getNobody();
        }

        if (!self::isProduct($Product)) {
            throw new QUI\Exception('No Product given');
        }

        $PriceField = $Product->getField(FieldHandler::FIELD_PRICE);
        $priceValue = $PriceField->getValue();

        // $priceValue may be NULL or empty string; in these cases, consider the default price field value as not set.
        if (empty($priceValue) && $priceValue != 0) {
            $priceValue = null;
        }

        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();

        // exists more price fields?
        // is user in group filter
        $priceFields = [];

        foreach (FieldHandler::getAllPriceFieldTypes() as $priceFieldType) {
            $priceFields = array_merge($priceFields, $Product->getFieldsByType($priceFieldType));
        }

        if (empty($priceFields)) {
            return new QUI\ERP\Money\Price($PriceField->getValue(), $Currency);
        }

        $priceFieldsConsidered = array_filter($priceFields, function ($Field) use ($User) {
            /* @var $Field QUI\ERP\Products\Field\UniqueField */

            // ignore default main price
            if ($Field->getId() == FieldHandler::FIELD_PRICE) {
                return false;
            }

            $options = $Field->getOptions();

            if (!empty($options['ignoreForPriceCalculation'])) {
                return false;
            }

            if (empty($options['groups'])) {
                return true;
            }

            $options['groups'] = trim($options['groups'], ',');
            $groups = explode(',', $options['groups']);

            foreach ($groups as $gid) {
                if ($User->isInGroup($gid)) {
                    return true;
                }
            }

            return false;
        });

        // use the lowest price?
        foreach ($priceFieldsConsidered as $Field) {
            $FieldClass = $Field;

            if ($Field instanceof UniqueField) {
                $FieldClass = FieldHandler::getField($Field->getId());
                $FieldClass->setValue($Field->getValue());
            }

            if (method_exists($FieldClass, 'onGetPriceFieldForProduct')) {
                try {
                    $value = $FieldClass->onGetPriceFieldForProduct($Product, $User);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                    continue;
                }
            } else {
                $value = $FieldClass->getValue();
            }

            if ($value === false || $value === '' || $value === null) {
                continue;
            }

            if (is_null($priceValue) || $value < $priceValue) {
                $priceValue = $value;
            }
        }

        if ($priceValue instanceof QUI\ERP\Money\Price) {
            $priceValue = $priceValue->getValue();
        }

        return new QUI\ERP\Money\Price($priceValue, $Currency);
    }

    /**
     * Return the editable fields for the project
     * editable fields can be changed by the user via the GUI
     *
     * @param ProductTypeInterface|null $Product
     * @return array
     */
    public static function getEditableFieldIdsForProduct(null | ProductTypeInterface $Product = null): array
    {
        if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $Product = $Product->getParent();
        }

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            if ($Product->getAttribute('editableVariantFields')) {
                $editable = $Product->getAttribute('editableVariantFields');

                if (is_string($editable)) {
                    $editable = json_decode($editable, true);
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
            $fieldIds = [];

            foreach ($fields as $fieldId => $isEditable) {
                if (!empty($isEditable)) {
                    $fieldIds[] = $fieldId;
                }
            }

            return $fieldIds;
        }

        $fields = FieldHandler::getFields();

        return array_map(function ($Field) {
            return $Field->getId();
        }, $fields);
    }

    /**
     * Return the inherited fields for the project
     *
     * @param ProductTypeInterface|null $Product
     * @return array
     */
    public static function getInheritedFieldIdsForProduct(null | ProductTypeInterface $Product = null): array
    {
        if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            $Product = $Product->getParent();
        }

        if ($Product instanceof QUI\ERP\Products\Product\Product) {
            if ($Product->getAttribute('inheritedVariantFields')) {
                $inherited = $Product->getAttribute('inheritedVariantFields');

                if (is_string($inherited)) {
                    $inherited = json_decode($inherited, true);
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
            $fieldIds = [];

            foreach ($fields as $fieldId => $isEditable) {
                if (!empty($isEditable)) {
                    $fieldIds[] = $fieldId;
                }
            }

            return $fieldIds;
        }


        $fields = FieldHandler::getFields();

        return array_map(function ($Field) {
            return $Field->getId();
        }, $fields);
    }

    /**
     * Return generate variant hash
     *
     * @param array $fields - could be a field array [Field, Field, Field],
     *                        or could be a field object list ['field-1':2, 'field-1':'value']
     * @return string
     */
    public static function generateVariantHashFromFields(array $fields): string
    {
        $hash = [];

        // get hash values
        foreach ($fields as $Field => $fieldValue) {
            if ($fieldValue instanceof QUI\ERP\Products\Interfaces\FieldInterface) {
                if (
                    method_exists($fieldValue, 'getOption')
                    && $fieldValue->getOption('exclude_from_variant_generation')
                ) {
                    continue;
                }

                $fieldId = $fieldValue->getId();
                $fieldValue = $fieldValue->getValue();
            } elseif (is_string($Field) || is_numeric($Field)) { // @phpstan-ignore-line
                $fieldId = $Field;
            } else {
                continue;
            }

            // string to hex
            if (!is_numeric($fieldValue)) {
                if (empty($fieldValue)) {
                    $fieldValue = '';
                }

                $fieldValue = implode(unpack("H*", $fieldValue));
            }

            $hash[] = $fieldId . ':' . $fieldValue;
        }

        // sort fields
        usort($hash, function ($a, $b) {
            $aId = (int)explode(':', $a)[0];
            $bId = (int)explode(':', $b)[0];

            return $aId - $bId;
        });

        // generate hash
        return ';' . implode(';', $hash) . ';';
    }

    /**
     * @param QUI\ERP\Products\Product\Product $Product
     */
    public static function setAvailableFieldOptions(QUI\ERP\Products\Product\Product $Product): void
    {
        if (
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) &&
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantChild)
        ) {
            return;
        }

        // attribute groups
        $groupList = $Product->getFieldsByType(FieldHandler::TYPE_ATTRIBUTE_GROUPS);

        $available = $Product->availableActiveChildFields();
        $availableHashes = $Product->availableActiveFieldHashes();
        $availableEntries = [];

        // parse allowed field values (=options)
        $currentVariantHash = Products::generateVariantHashFromFields($groupList);
        $searchHashes = FieldUtils::getSearchHashesFromFieldHash($currentVariantHash);

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
                    if (fnmatch($searchHash, $hash)) {
                        $availableEntries[$fieldId][$fieldValue] = true;
                        break;
                    }
                }
            }
        }


        // set field option status
        foreach ($groupList as $Field) {
            $fieldId = $Field->getId();

            if (method_exists($Field, 'hideEntries')) {
                $Field->hideEntries();
            }

            if (method_exists($Field, 'disableEntries')) {
                $Field->disableEntries();
            }

            if (!method_exists($Field, 'getOptions')) {
                $entries = [];
            } else {
                $options = $Field->getOptions();
                $entries = $options['entries'];
            }

            if (!isset($available[$fieldId])) {
                continue;
            }

            $allowed = $available[$fieldId];
            $allowed = array_flip($allowed);

            foreach ($entries as $key => $value) {
                $valueId = $value['valueId'];
                $hashedValueId = false;

                if (!is_numeric($valueId)) {
                    $hashedValueId = implode(unpack("H*", $valueId));

                    if (!isset($allowed[$valueId]) && !isset($allowed[$hashedValueId])) {
                        continue;
                    }
                }

                if (!isset($allowed[$valueId])) {
                    continue;
                }

                if (method_exists($Field, 'showEntry')) {
                    $Field->showEntry($key);
                }

                if (isset($availableEntries[$fieldId][$valueId]) && method_exists($Field, 'enableEntry')) {
                    $Field->enableEntry($key);
                    continue;
                }

                if (
                    $hashedValueId
                    && isset($availableEntries[$fieldId][$hashedValueId])
                    && method_exists($Field, 'enableEntry')
                ) {
                    $Field->enableEntry($key);
                }
            }
        }
    }

    /**
     * @param QUI\ERP\Products\Product\Product $Product
     * @return array
     */
    public static function getJsFieldHashArray(QUI\ERP\Products\Product\Product $Product): array
    {
        if (
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) &&
            !($Product instanceof QUI\ERP\Products\Product\Types\VariantChild)
        ) {
            return [];
        }

        $availableHashes = $Product->availableActiveFieldHashes();
        $result = [];

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
    public static function isVariant($Product): bool
    {
        if ($Product instanceof QUI\ERP\Products\Product\ViewFrontend) {
            $Product = $Product->getProduct();
        }

        if (
            $Product instanceof QUI\ERP\Products\Product\Types\VariantParent
            || $Product instanceof QUI\ERP\Products\Product\Types\VariantChild
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param array $urlFieldValue
     * @param integer $categoryId
     * @param bool|integer $ignoreProductId - optional
     *
     * @throws Exception|QUI\Exception
     */
    public static function checkUrlByUrlFieldValue(
        array $urlFieldValue,
        int $categoryId,
        bool | int $ignoreProductId = false
    ): void {
        if (empty($urlFieldValue)) {
            return;
        }

        $urlCacheField = 'F' . FieldHandler::FIELD_URL;
        $table = QUI\ERP\Products\Utils\Tables::getProductCacheTableName();

        $where = [];
        $binds = [];
        $i = 0;

        foreach ($urlFieldValue as $lang => $url) {
            if (empty($url)) {
                continue;
            }

            self::checkUrlLength($url, $lang, $categoryId);

            $binds[':lang' . $i] = $lang;
            $binds[':url' . $i] = $url;
            $binds[':category' . $i] = '%,' . $categoryId . ',%';

            $where[] = "(F19 LIKE :url$i AND lang LIKE :lang$i AND category LIKE :category$i)";
            $i++;
        }

        if (empty($where)) {
            return;
        }

        $where = implode(' OR ', $where);

        $query = "
            SELECT id, $urlCacheField 
            FROM {$table}
            WHERE {$where}
        ";

        $PDO = QUI::getDataBase()->getPDO();
        $Statement = $PDO->prepare($query);

        foreach ($binds as $bind => $value) {
            $Statement->bindValue($bind, $value);
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
     * @throws Exception|QUI\Exception
     */
    public static function checkUrlLength(string $url, string $lang, int $categoryId): void
    {
        try {
            $Category = Categories::getCategory($categoryId);
            $projects = QUI::getProjectManager()->getProjects(true);
        } catch (QUI\Exception) {
            return;
        }

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            if ($Project->getLang() !== $lang) {
                continue;
            }

            $categoryUrl = $Category->getUrl($Project);

            if (!empty($categoryUrl) && strlen($categoryUrl . '/' . $url) > 2000) {
                throw new Exception([
                    'quiqqer/products',
                    'exception.url.is.too.long'
                ]);
            }
        }
    }

    /**
     * @param $Product
     * @return int
     */
    public static function getBasketCondition($Product): int
    {
        // fields -> BasketConditions
        $conditions = $Product->getFieldsByType('BasketConditions');

        if (!count($conditions)) {
            return QUI\ERP\Products\Field\Types\BasketConditions::TYPE_1;
        }

        $return = $conditions[0]->getValue();

        if ($return) {
            return $return;
        }

        return QUI\ERP\Products\Field\Types\BasketConditions::TYPE_1;
    }
}
