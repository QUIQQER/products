<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class FieldAjaxTest extends AjaxTestCase
{
    public function testFieldCreateReadUpdateAndDeleteLifecycle(): void
    {
        $fieldId = 1601;
        $created = $this->createField($fieldId, 'ajax-field-lifecycle');
        self::assertSame($fieldId, (int)$created['id']);
        self::assertSame(Fields::TYPE_INPUT, $created['type']);
        self::assertTrue((bool)$created['isPublic']);

        $read = $this->invokeEndpoint(
            'fields/get.php',
            'package_quiqqer_products_ajax_fields_get',
            $fieldId
        );
        self::assertSame($fieldId, (int)$read['id']);
        self::assertSame(7, (int)$read['priority']);

        $result = $this->invokeEndpoint(
            'fields/update.php',
            'package_quiqqer_products_ajax_fields_update',
            $fieldId,
            json_encode([
                'priority' => 31,
                'publicField' => 0,
                'defaultValue' => 'fallback',
                'options' => ['placeholder' => 'SKU']
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame(Fields::PRODUCT_ARRAY_CHANGED, $result);

        $Reloaded = Fields::getField($fieldId);
        self::assertSame(31, (int)$Reloaded->getAttribute('priority'));
        self::assertFalse($Reloaded->isPublic());
        self::assertSame('fallback', $Reloaded->getDefaultValue());
        self::assertSame('SKU', $Reloaded->getOptions()['placeholder']);

        self::assertSame(['placeholder' => 'SKU'], $this->invokeEndpoint(
            'fields/getFieldOptions.php',
            'package_quiqqer_products_ajax_fields_getFieldOptions',
            $fieldId
        ));
        self::assertContains('text', $this->invokeEndpoint(
            'fields/getSearchTypesForField.php',
            'package_quiqqer_products_ajax_fields_getSearchTypesForField',
            $fieldId
        ));

        $this->invokeEndpoint(
            'fields/deleteChild.php',
            'package_quiqqer_products_ajax_fields_deleteChild',
            $fieldId
        );
        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);
        Fields::getField($fieldId);
    }

    public function testFieldCollectionEndpointsReturnFilteredPersistedData(): void
    {
        $firstId = 1602;
        $secondId = 1603;
        $this->createField($firstId, 'ajax-field-first');
        $this->createField($secondId, 'ajax-field-second');
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $originalSortFields = $Config?->get('products', 'sortFields');

        try {
            $children = $this->invokeEndpoint(
                'fields/getChildren.php',
                'package_quiqqer_products_ajax_fields_getChildren',
                json_encode([$firstId, $secondId, 999999], JSON_THROW_ON_ERROR)
            );
            self::assertSame([$firstId, $secondId], array_map(
                'intval',
                array_column($children, 'id')
            ));

            $multiple = $this->invokeEndpoint(
                'fields/getFields.php',
                'package_quiqqer_products_ajax_fields_getFields',
                json_encode([$firstId, $secondId], JSON_THROW_ON_ERROR)
            );
            self::assertSame([$firstId, $secondId], array_map(
                'intval',
                array_column($multiple, 'id')
            ));

            $grid = $this->invokeEndpoint(
                'fields/list.php',
                'package_quiqqer_products_ajax_fields_list',
                json_encode([
                    'type' => Fields::TYPE_INPUT,
                    'perPage' => 200,
                    'page' => 1
                ], JSON_THROW_ON_ERROR)
            );
            self::assertGreaterThanOrEqual(2, $grid['total']);
            self::assertContains($firstId, array_map('intval', array_column($grid['data'], 'id')));
            self::assertContains($secondId, array_map('intval', array_column($grid['data'], 'id')));

            $search = $this->invokeEndpoint(
                'fields/search.php',
                'package_quiqqer_products_ajax_fields_search',
                json_encode(['name' => ProductTestHelper::PREFIX . 'ajax-field-first'], JSON_THROW_ON_ERROR),
                json_encode(['limit' => 20], JSON_THROW_ON_ERROR)
            );
            self::assertSame([$firstId], array_map('intval', array_column($search, 'id')));

            $public = $this->invokeEndpoint(
                'fields/getPublicFields.php',
                'package_quiqqer_products_ajax_fields_getPublicFields'
            );
            self::assertContains($firstId, array_map('intval', array_column($public, 'id')));

            self::assertNotEmpty($this->invokeEndpoint(
                'fields/getStandardFields.php',
                'package_quiqqer_products_ajax_fields_getStandardFields'
            ));
            self::assertNotEmpty($this->invokeEndpoint(
                'fields/getSystemFields.php',
                'package_quiqqer_products_ajax_fields_getSystemFields'
            ));
            self::assertNotEmpty($this->invokeEndpoint(
                'fields/getFieldTypes.php',
                'package_quiqqer_products_ajax_fields_getFieldTypes'
            ));
            self::assertIsArray($this->invokeEndpoint(
                'fields/getFieldTypeSettings.php',
                'package_quiqqer_products_ajax_fields_getFieldTypeSettings'
            ));
            $Config?->set('products', 'sortFields', 'Sc_date,F' . $firstId);
            $sortable = $this->invokeEndpoint(
                'fields/getSortableFields.php',
                'package_quiqqer_products_ajax_fields_getSortableFields'
            );
            self::assertContains('Sc_date', array_column($sortable, 'id'));
            self::assertContains('F' . $firstId, array_column($sortable, 'id'));
        } finally {
            if ($originalSortFields === false) {
                $Config?->del('products', 'sortFields');
            } else {
                $Config?->set('products', 'sortFields', $originalSortFields);
            }

            $this->invokeEndpoint(
                'fields/deleteChildren.php',
                'package_quiqqer_products_ajax_fields_deleteChildren',
                json_encode([$firstId, $secondId], JSON_THROW_ON_ERROR)
            );
        }

        $messages = QUI::getMessagesHandler()->getMessagesAsArray(QUI::getUsers()->getSystemUser());
        self::assertSame([], $messages, json_encode($messages, JSON_PRETTY_PRINT) ?: 'Messages could not be encoded.');
        $runtimeFields = (new ReflectionProperty(Fields::class, 'list'))->getValue();
        self::assertArrayNotHasKey($firstId, $runtimeFields);
        self::assertArrayNotHasKey($secondId, $runtimeFields);

        foreach ([$firstId, $secondId] as $fieldId) {
            self::assertSame(0, (int)QUI::getDataBaseConnection()->fetchOne(
                'SELECT COUNT(*) FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName())
                . ' WHERE id = ?',
                [$fieldId]
            ));

            try {
                Fields::getField($fieldId);
                self::fail('Deleted field #' . $fieldId . ' is still available.');
            } catch (Exception $Exception) {
                self::assertSame(404, $Exception->getCode());
            }
        }
    }

    public function testSortableFieldsForSiteExposeSystemAndConfiguredFieldEntries(): void
    {
        ProductTestHelper::installCompleteFieldFixtures();
        $Project = ProductTestHelper::getProject();
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        $originalSortFields = $Config?->get('products', 'sortFields');

        try {
            $Config?->set('products', 'sortFields', 'Sc_date,Se_date,F' . Fields::FIELD_PRICE);
            $result = $this->invokeEndpointAsAdmin(
                'fields/getSortableFieldsForSite.php',
                'package_quiqqer_products_ajax_fields_getSortableFieldsForSite',
                ProductTestHelper::getCategorySite()->getId(),
                json_encode([
                    'name' => $Project->getName(),
                    'lang' => $Project->getLang()
                ], JSON_THROW_ON_ERROR)
            );
        } finally {
            if ($originalSortFields === false) {
                $Config?->del('products', 'sortFields');
            } else {
                $Config?->set('products', 'sortFields', $originalSortFields);
            }
        }

        self::assertGreaterThanOrEqual(2, count($result));
        self::assertSame(['Se_date', 'Sc_date'], array_slice(array_column($result, 'id'), 0, 2));
        self::assertSame(
            ['id', 'idDisplay', 'title', 'sorting'],
            array_keys($result[0])
        );
        self::assertContainsOnly('bool', array_column($result, 'sorting'));
    }

    /** @return array<string, mixed> */
    private function createField(int $fieldId, string $name): array
    {
        return $this->invokeEndpoint(
            'fields/create.php',
            'package_quiqqer_products_ajax_fields_create',
            json_encode([
                'id' => $fieldId,
                'name' => ProductTestHelper::PREFIX . $name,
                'type' => Fields::TYPE_INPUT,
                'search_type' => 'text',
                'priority' => 7,
                'publicField' => 1,
                'titles' => ['de' => $name, 'en' => $name]
            ], JSON_THROW_ON_ERROR)
        );
    }
}
