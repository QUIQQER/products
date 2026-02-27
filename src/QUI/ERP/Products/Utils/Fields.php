<?php

/**
 * This file contains QUI\ERP\Products\Utils\Fields
 */

namespace QUI\ERP\Products\Utils;

use DOMXPath;
use QUI;
use QUI\ERP\Products\Field\Field;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Product\Model as ProductModel;
use QUI\Utils\DOM;

use function array_keys;
use function array_map;
use function explode;
use function file_exists;
use function floatval;
use function implode;
use function is_numeric;
use function is_object;
use function is_string;
use function strnatcmp;
use function trim;
use function unpack;
use function usort;

/**
 * Class Fields
 */
class Fields
{
    /**
     * @param array $fields
     * @return array
     * @deprecated riesen quatsch
     *
     * @todo wer hat diese methode gebaut? ToJson = return string, wieso array?
     */
    public static function parseFieldsToJson(array $fields = []): array
    {
        $result = [];

        foreach ($fields as $Field) {
            if (!self::isField($Field)) {
                continue;
            }

            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            try {
                self::validateField($Field);

                $result[] = $Field->toProductArray();
            } catch (QUI\Exception) {
            }
        }

        return $result;
    }

    /**
     * @param $fieldHash
     * @return array
     */
    public static function parseFieldHashToArray($fieldHash): array
    {
        $result = [];
        $fieldHash = trim($fieldHash, ';');

        if (empty($fieldHash)) {
            return $result;
        }

        $fieldHash = explode(';', $fieldHash);

        foreach ($fieldHash as $entry) {
            $entry = explode(':', $entry);
            $entry[0] = (int)$entry[0];

            $result[$entry[0]] = $entry[1];
        }

        return $result;
    }

    /**
     * Return all search hashes from one field hash
     *
     * @param string $hash
     * @return array
     */
    public static function getSearchHashesFromFieldHash(string $hash): array
    {
        $hashes = self::parseFieldHashToArray($hash);
        $foundEmptyValues = false;

        $hashes = array_map(function ($entry) use (&$foundEmptyValues) {
            if ($entry === '') {
                $foundEmptyValues = true;

                return '*';
            }

            return $entry;
        }, $hashes);

        $searchHashes = [];

        foreach ($hashes as $fieldId => $value) {
            $clone = $hashes;

            if (!$foundEmptyValues) {
                $clone[$fieldId] = '*';
            }

            $searchHashes[self::generateFieldHashFromArray($clone)] = true;

            if (!$foundEmptyValues) {
                continue;
            }

            try {
                $Field = FieldHandler::getField($fieldId);
                $options = $Field->getOptions();

                if (!isset($options['entries'])) {
                    continue;
                }

                foreach ($options['entries'] as $option) {
                    $clone[$fieldId] = $option['valueId'];
                    $generatedHash = self::generateFieldHashFromArray($clone);

                    $searchHashes[$generatedHash] = true;

                    if (!is_numeric($option['valueId'])) {
                        $clone[$fieldId] = implode(unpack("H*", $option['valueId']));
                        $generatedHash = self::generateFieldHashFromArray($clone);

                        $searchHashes[$generatedHash] = true;
                    }
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }


        return array_keys($searchHashes);
    }

    /**
     * @param $field
     * @return string
     */
    protected static function generateFieldHashFromArray($field): string
    {
        $result = [];

        foreach ($field as $k => $ce) {
            $result[] = $k . ':' . $ce;
        }

        return ';' . implode(';', $result) . ';';
    }

    /**
     * Is the object a product field?
     *
     * @param mixed $object
     * @return boolean
     */
    public static function isField(mixed $object): bool
    {
        if (!is_object($object)) {
            return false;
        }

        if ($object instanceof QUI\ERP\Products\Interfaces\FieldInterface) {
            return true;
        }

        return false;
    }

    /**
     * Validate the value of the field
     *
     * @param QUI\ERP\Products\Interfaces\FieldInterface $Field
     * @throws QUI\Exception
     */
    public static function validateField(QUI\ERP\Products\Interfaces\FieldInterface $Field): void
    {
        $Field->validate($Field->getValue());
    }

    /**
     * Sort the fields by priority
     *
     * @param array $fields - FieldInterface[]
     * @param string $sort - sorting field
     * @return FieldInterface[]
     */
    public static function sortFields(array $fields, string $sort = 'priority'): array
    {
        if (empty($fields)) {
            return $fields;
        }

        // allowed sorting
        switch ($sort) {
            case 'id':
            case 'title':
            case 'type':
            case 'name':
            case 'priority':
            case 'workingtitle':
                break;

            default:
                $sort = 'priority';
        }

        // if memory cache exists
        $cache = FieldSortCache::getFieldCache($fields, $sort);

        if ($cache) {
            return $cache;
        }

        // if no memory cache exists

        /**
         * @param QUI\ERP\Products\Field\Field $Field
         * @param string $field
         * @return int|string
         */
        $getFieldSortValue = function (QUI\ERP\Products\Field\Field $Field, string $field) {
            if ($field === 'id') {
                return $Field->getId();
            }

            if ($field === 'title') {
                return $Field->getTitle();
            }

            if ($field === 'type') {
                return $Field->getType();
            }

            if ($field === 'name') {
                return $Field->getName();
            }

            if ($field === 'workingtitle') {
                return $Field->getWorkingTitle();
            }

            return (int)$Field->getAttribute($field);
        };

        usort($fields, function ($Field1, $Field2) use ($sort, $getFieldSortValue) {
            if (!self::isField($Field1)) {
                return 1;
            }

            if (!self::isField($Field2)) {
                return -1;
            }

            /* @var $Field1 QUI\ERP\Products\Field\Field */
            /* @var $Field2 QUI\ERP\Products\Field\Field */
            $priority1 = $getFieldSortValue($Field1, $sort);
            $priority2 = $getFieldSortValue($Field2, $sort);

            if (is_string($priority1) || is_string($priority2)) {
                return strnatcmp($priority1, $priority2);
            }

            // if sorting is priority, and both are equal, than use title
            if ($sort === 'priority' && $priority1 === $priority2) {
                $priority1 = $getFieldSortValue($Field1, 'title');
                $priority2 = $getFieldSortValue($Field2, 'title');

                return strnatcmp($priority1, $priority2);
            }

            if ($priority1 === 0) {
                return 1;
            }

            if ($priority2 === 0) {
                return -1;
            }

            if ($priority1 < $priority2) {
                return -1;
            }

            if ($priority1 > $priority2) {
                return 1;
            }

            return 0;
        });

        // cache the sorting
        $cache = [];

        foreach ($fields as $Field) {
            $cache[] = $Field->getId();
        }

        FieldSortCache::setFieldCache($fields, $sort, $cache);

        return $fields;
    }

    /**
     * Can the field used as a detail field?
     * JavaScript equivalent package/quiqqer/products/bin/utils/Fields
     *
     * @param mixed $Field
     * @return bool
     */
    public static function canUsedAsDetailField(mixed $Field): bool
    {
        /* @var $Field QUI\ERP\Products\Field\Field */
        if (!self::isField($Field)) {
            return false;
        }

        if (
            $Field->getId() == FieldHandler::FIELD_TITLE
            || $Field->getId() == FieldHandler::FIELD_CONTENT
            || $Field->getId() == FieldHandler::FIELD_SHORT_DESC
            || $Field->getId() == FieldHandler::FIELD_PRICE
            || $Field->getId() == FieldHandler::FIELD_IMAGE
        ) {
            return false;
        }

        if (
            $Field->getType() == FieldHandler::TYPE_ATTRIBUTE_LIST
            || $Field->getType() == FieldHandler::TYPE_FOLDER
            || $Field->getType() == FieldHandler::TYPE_TEXTAREA
            || $Field->getType() == FieldHandler::TYPE_TEXTAREA_MULTI_LANG
        ) {
            return false;
        }

        return true;
    }

    /**
     * Show the field in the details?
     *
     * @param mixed $Field
     * @return bool
     */
    public static function showFieldInProductDetails(mixed $Field): bool
    {
        /* @var $Field QUI\ERP\Products\Field\Field */
        if (!self::canUsedAsDetailField($Field)) {
            return false;
        }

        return $Field->showInDetails();
    }

    /**
     * Returns the value from a Weight Field in Kilogram
     *
     * @param Field $Field
     * @return float|int
     */
    public static function weightFieldToKilogram(QUI\ERP\Products\Field\Field $Field): float | int
    {
        if ($Field->getId() !== QUI\ERP\Products\Handler\Fields::FIELD_WEIGHT) {
            return 0;
        }

        $value = $Field->getValue();

        if (empty($value)) {
            return 0;
        }

        return self::weightToKilogram($value['quantity'], $value['id']);
    }

    /**
     * Parses a weight value to kilogram
     *
     * @param float|int|string $value
     * @param string $unit - kg, g, t, tons, lbs, lb
     * @return float|int
     */
    public static function weightToKilogram(float | int | string $value, string $unit): float | int
    {
        $value = floatval($value);

        if ($unit === 'kg') {
            return $value;
        }

        if (empty($unit)) {
            return $value;
        }

        return match ($unit) {
            'g' => $value / 1000,
            't', 'tons' => $value * 1000,
            'lb', 'lbs' => $value / 2.2046,
            default => $value,
        };
    }

    /**
     * is the value a weight specification
     *
     * @param $weight
     * @return bool
     */
    public static function isWeight($weight): bool
    {
        return match ($weight) {
            'g', 'kg', 't', 'tons', 'lb', 'lbs' => true,
            default => false,
        };
    }

    /**
     * compares to numbers
     *
     * @param $no1
     * @param $no2
     * @param $type
     *
     * @return bool
     */
    public static function compare($no1, $no2, $type): bool
    {
        if ($type === '=') {
            return $no1 == $no2;
        }

        if ($type === 'gt') {
            return $no1 > $no2;
        }

        if ($type === 'egt') {
            return $no1 >= $no2;
        }

        if ($type === 'lt') {
            return $no1 < $no2;
        }

        if ($type === 'elt') {
            return $no1 <= $no2;
        }

        return false;
    }

    /**
     * Parses the term unit to human-readable term
     *
     * egt = >=
     *
     * @param $term
     * @return string
     */
    public static function termToHuman($term): string
    {
        if ($term === '=') {
            return '=';
        }

        if ($term === 'gt') {
            return '>';
        }

        if ($term === 'egt') {
            return '>=';
        }

        if ($term === 'lt') {
            return '<';
        }

        if ($term === 'elt') {
            return '<=';
        }

        return '';
    }

    /**
     *
     * @param ProductModel|null $Product (optional) - Get panel field categories for this specific product only
     * @return array
     *
     * @todo cachinge
     */
    public static function getPanelFieldCategories(?ProductModel $Product = null): array
    {
        $plugins = QUI::getPackageManager()->getInstalled();
        $categories = [];

        foreach ($plugins as $plugin) {
            $xml = OPT_DIR . $plugin['name'] . '/products.xml';

            if (!file_exists($xml)) {
                continue;
            }

            $Dom = QUI\Utils\Text\XML::getDomFromXml($xml);
            $Path = new DOMXPath($Dom);

            $categoryList = $Path->query("//quiqqer/products/fieldCategories/fieldCategory");

            foreach ($categoryList as $Category) {
                if (
                    !method_exists($Category, 'getAttribute')
                    || !method_exists($Category, 'getElementsByTagName')
                ) {
                    continue;
                }

                $name = $Category->getAttribute('name');
                $name = trim($name);

                $Title = $Category->getElementsByTagName('title');
                $title = '';

                if ($Title && $Title->item(0)) {
                    $title = DOM::getTextFromNode($Title->item(0), false);
                }

                $Icon = $Category->getElementsByTagName('icon');
                $icon = '';

                if ($Icon && $Icon->item(0)) {
                    $icon = trim($Icon->item(0)->nodeValue);
                }

                // fields
                $fields = $Category->getElementsByTagName('fields');
                $fieldIds = [];

                if ($fields->length) {
                    $f = $fields->item(0)->getElementsByTagName('field');

                    foreach ($f as $Field) {
                        $fieldId = (int)trim($Field->nodeValue);

                        if ($Product && !$Product->hasField($fieldId)) {
                            continue;
                        }

                        $fieldIds[] = $fieldId;
                    }
                }

                if (empty($fieldIds)) {
                    continue;
                }

                $categories[] = [
                    'name' => $name,
                    'text' => $title,
                    'icon' => $icon,
                    'fields' => $fieldIds
                ];
            }
        }

        return $categories;
    }

    /**
     * Return the fields of the Field Category
     * Field Category = Grouped Fields
     *
     * @param String $category - name of the category
     * @param ProductModel|null $Product (optional) - Get category fields for this specific product only
     * @return array
     */
    public static function getPanelFieldCategoryFields(string $category, ?ProductModel $Product = null): array
    {
        $category = str_replace('fieldCategory-', '', $category);

        $fields = [];
        $allCategories = self::getPanelFieldCategories();
        $fieldIds = [];

        // check field ids
        foreach ($allCategories as $catData) {
            if ($catData['name'] !== $category) {
                continue;
            }

            foreach ($catData['fields'] as $fieldId) {
                if (isset($fieldIds[$fieldId])) {
                    continue;
                }

                if ($Product && !$Product->hasField($fieldId)) {
                    continue;
                }

                try {
                    $Field = QUI\ERP\Products\Handler\Fields::getField($fieldId);
                    $fields[] = $Field->getAttributes();

                    $fieldIds[$fieldId] = true;
                } catch (QUI\Exception) {
                }
            }
        }

        // check field types
        $plugins = QUI::getPackageManager()->getInstalled();

        foreach ($plugins as $plugin) {
            $xml = OPT_DIR . $plugin['name'] . '/products.xml';

            if (!file_exists($xml)) {
                continue;
            }

            $Dom = QUI\Utils\Text\XML::getDomFromXml($xml);
            $Path = new DOMXPath($Dom);

            $fieldList = $Path->query("//quiqqer/products/fields/field[@fieldCategory='$category']");

            foreach ($fieldList as $NodeField) {
                if (!method_exists($NodeField, 'getAttribute')) {
                    continue;
                }

                $fieldType = $NodeField->getAttribute('name');
                $list = QUI\ERP\Products\Handler\Fields::getFieldsByType($fieldType);

                foreach ($list as $Field) {
                    $fieldId = $Field->getId();

                    if ($Product && !$Product->hasField($fieldId)) {
                        continue;
                    }

                    if (isset($fieldIds[$fieldId])) {
                        continue;
                    }

                    $fields[] = $Field->getAttributes();
                    $fieldIds[$fieldId] = true;
                }
            }
        }

        return $fields;
    }
}
