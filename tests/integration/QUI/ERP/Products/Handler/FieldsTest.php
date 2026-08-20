<?php

namespace QUITests\ERP\Products\Integration\Handler;

use QUI;
use QUI\ERP\Products\Field\Exception as FieldException;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Field\Types\Date;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\Types\Price;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class FieldsTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testRegisteredFieldTypesContainCoreProductTypes(): void
    {
        $registeredClasses = array_column(Fields::getFieldTypes(), 'src');

        self::assertContains(Date::class, $registeredClasses);
        self::assertContains(Price::class, $registeredClasses);
    }

    public function testFieldCreationWithoutIdUsesCustomRangeAndAttributeDefaults(): void
    {
        $Input = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'name' => 'PHPUnit automatically numbered field',
            'type' => Fields::TYPE_INPUT,
            'publicField' => 1
        ]));
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1901,
            'name' => 'PHPUnit attribute defaults',
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'publicField' => 1
        ]));

        try {
            self::assertGreaterThanOrEqual(1000, $Input->getId());
            self::assertSame('PHPUnit automatically numbered field', $Input->getAttribute('name'));
            self::assertTrue((bool)$Attribute->getOption('exclude_from_variant_generation'));
            self::assertSame($Attribute->getId(), Fields::getField($Attribute->getId())->getId());
        } finally {
            ProductTestHelper::runAsSystemUser(static function () use ($Input, $Attribute): void {
                $Input->delete();
                $Attribute->delete();
            });
        }
    }

    public function testFieldCreationRejectsMissingUnknownAndDuplicateTypes(): void
    {
        foreach (
            [
                [],
                ['type' => 'DefinitelyUnknownFieldType'],
                ['id' => Fields::FIELD_PRICE, 'type' => Fields::TYPE_INPUT]
            ] as $attributes
        ) {
            try {
                ProductTestHelper::runAsSystemUser(static fn () => Fields::createField($attributes));
                self::fail('Invalid field creation input must be rejected.');
            } catch (FieldException $Exception) {
                self::assertNotSame('', $Exception->getMessage());
            }
        }
    }

    public function testFieldClassificationFactoriesAndTypeMetadataAreStable(): void
    {
        self::assertFalse(Fields::isField('field'));
        self::assertTrue(Fields::isField(new UniqueField(9999)));
        self::assertTrue(Fields::isField(Fields::getField(Fields::FIELD_PRICE)));

        $Input = Fields::getFieldByType(Fields::TYPE_INPUT, 1910, ['public' => true]);
        self::assertInstanceOf(Input::class, $Input);
        self::assertSame(1910, $Input->getId());

        try {
            Fields::getFieldByType('DefinitelyUnknownFieldType', 1911);
            self::fail('An unknown field class must be rejected.');
        } catch (FieldException $Exception) {
            self::assertStringContainsString('exception.field.type_not_found', $Exception->getMessage());
        }

        self::assertSame([], Fields::getFieldTypeData('DefinitelyUnknownFieldType'));
        self::assertSame(
            Input::class,
            Fields::getFieldTypeData(Fields::TYPE_INPUT)['src']
        );
    }

    public function testFieldQueriesApplyFiltersSortingPaginationAndCounts(): void
    {
        $ids = Fields::getFieldIds([
            'where_or' => [
                'id' => Fields::FIELD_PRICE,
                'requiredField' => 1
            ],
            'order' => 'id DESC',
            'limit' => 2
        ]);

        $orderedIds = array_values(array_map('intval', array_column($ids, 'id')));
        $descendingIds = $orderedIds;
        rsort($descendingIds);
        self::assertNotEmpty($ids);
        self::assertLessThanOrEqual(2, count($ids));
        self::assertSame($descendingIds, $orderedIds);
        $fallbackIds = array_values(array_map('intval', array_column(Fields::getFieldIds([
            'where' => ['systemField' => 1],
            'order' => 'not-an-allowed-order',
            'limit' => 2
        ]), 'id')));
        $ascendingFallbackIds = $fallbackIds;
        sort($ascendingFallbackIds);
        self::assertSame($ascendingFallbackIds, $fallbackIds);
        self::assertNotEmpty(Fields::getFieldsByType(Fields::TYPE_INPUT));
        self::assertSame(
            count(Fields::getSystemFields()),
            Fields::countFields(['where' => ['systemField' => 1]])
        );
        self::assertGreaterThanOrEqual(
            Fields::countFields(['where' => ['requiredField' => 1]]),
            Fields::countFields(['where_or' => ['requiredField' => 1, 'publicField' => 1]])
        );
    }

    public function testUnknownPersistedFieldTypeIsRejectedWithDatabaseContext(): void
    {
        $row = QUI::getDataBaseConnection()->fetchAssociative(
            'SELECT * FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getFieldTableName()) . ' WHERE id = ?',
            [Fields::FIELD_PRICE]
        );
        self::assertIsArray($row);
        $row['id'] = 1912;
        $row['type'] = 'DefinitelyUnknownFieldType';
        QUI::getDataBaseConnection()->insert(Tables::getFieldTableName(), $row);
        Fields::removeRuntimeField(1912);
        $DeletedFields = new ReflectionProperty(Fields::class, 'deletedFieldIds');
        $deleted = $DeletedFields->getValue();
        unset($deleted[1912]);
        $DeletedFields->setValue(null, $deleted);

        try {
            Fields::getField(1912);
            self::fail('A persisted field with an unknown class must be rejected.');
        } catch (FieldException $Exception) {
            self::assertSame(404, $Exception->getCode());
            self::assertSame('DefinitelyUnknownFieldType', $Exception->getContext()['type']);
            self::assertSame('', $Exception->getContext()['class']);
        } finally {
            QUI::getDataBaseConnection()->delete(Tables::getFieldTableName(), ['id' => 1912]);
            Fields::removeRuntimeField(1912);
        }
    }

    public function testFieldTypeAndPriceProviderCachesReturnConsistentResults(): void
    {
        Fields::clearCache();
        $types = Fields::getFieldTypes();
        $FieldTypes = new ReflectionProperty(Fields::class, 'fieldTypes');
        $FieldTypes->setValue(null, null);
        self::assertSame($types, Fields::getFieldTypes());

        $priceTypes = Fields::getAllPriceFieldTypes();
        self::assertContains(Fields::TYPE_PRICE, $priceTypes);
        self::assertContains(Fields::TYPE_PRICE_BY_QUANTITY, $priceTypes);
        self::assertContains(Fields::TYPE_PRICE_BY_TIMEPERIOD, $priceTypes);
        $providerCache = QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'price_field_types';
        QUI\Cache\LongTermCache::clear($providerCache);
        $providerTypes = Fields::getPriceFieldTypesByProviders();
        self::assertSame($providerTypes, Fields::getPriceFieldTypesByProviders());
    }

    public function testPriceFactorSettingsAreParsedFromIsolatedPackageConfiguration(): void
    {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $originalSetting = $Config->get('products', 'priceFieldFactors');
        $RuntimeSettings = new ReflectionProperty(Fields::class, 'priceFactorSettings');
        $originalRuntimeSetting = $RuntimeSettings->getValue();

        try {
            $Config->set('products', 'priceFieldFactors', json_encode([
                Fields::FIELD_PRICE_OFFER => [
                    'sourceFieldId' => Fields::FIELD_PRICE,
                    'multiplier' => 1.5
                ]
            ], JSON_THROW_ON_ERROR));
            $RuntimeSettings->setValue(null, false);
            self::assertSame(1.5, Fields::getPriceFactorSettings()[Fields::FIELD_PRICE_OFFER]['multiplier']);

            $Config->set('products', 'priceFieldFactors', 'invalid-json');
            $RuntimeSettings->setValue(null, false);
            self::assertSame([], Fields::getPriceFactorSettings());
        } finally {
            if ($originalSetting === false) {
                $Config->del('products', 'priceFieldFactors');
            } else {
                $Config->set('products', 'priceFieldFactors', $originalSetting);
            }
            $RuntimeSettings->setValue(null, $originalRuntimeSetting);
        }
    }

    public function testProductFieldAttributeSynchronizationPreservesGlobalSwitches(): void
    {
        $Product = ProductTestHelper::createProduct('field-attribute-sync', 33);
        $Canonical = Fields::getField(Fields::FIELD_PRICE);
        $ProductPrice = $Product->getField(Fields::FIELD_PRICE);
        $ProductPrice->setPublicStatus(!$Canonical->isPublic());
        $ProductPrice->setShowInDetailsStatus(!$Canonical->showInDetails());
        ProductTestHelper::runAsSystemUser(
            static fn ($SystemUser) => $Product->save($SystemUser)
        );

        $originalEvents = Products::$fireEventsOnProductSave;
        $originalSearchCache = Products::$updateProductSearchCache;
        Products::$fireEventsOnProductSave = false;
        Products::$updateProductSearchCache = false;

        try {
            Fields::setFieldAttributesToProducts(Fields::FIELD_PRICE, [
                'ownField' => true,
                'unassigned' => false
            ]);
            $eventsAfterSynchronization = Products::$fireEventsOnProductSave;
            $searchCacheAfterSynchronization = Products::$updateProductSearchCache;
        } finally {
            Products::$fireEventsOnProductSave = $originalEvents;
            Products::$updateProductSearchCache = $originalSearchCache;
        }

        $Reloaded = Products::getNewProductInstance($Product->getId());
        $ReloadedPrice = $Reloaded->getField(Fields::FIELD_PRICE);

        self::assertSame($Canonical->isPublic(), $ReloadedPrice->isPublic());
        self::assertSame($Canonical->showInDetails(), $ReloadedPrice->showInDetails());
        self::assertTrue($ReloadedPrice->isOwnField());
        self::assertFalse($ReloadedPrice->isUnassigned());
        self::assertFalse($eventsAfterSynchronization);
        self::assertFalse($searchCacheAfterSynchronization);
    }
}
