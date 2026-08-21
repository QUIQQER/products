<?php

namespace QUITests\ERP\Products\Integration\Product;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Fixtures\TestUser;

class VariantLifecycleTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testParentCreatesChildAndPersistsDefaultVariant(): void
    {
        $Parent = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'variant-parent',
            50,
            VariantParent::class
        );
        self::assertInstanceOf(VariantParent::class, $Parent);
        self::assertNotSame('', VariantParent::getTypeTitle());
        self::assertNotSame('', VariantParent::getTypeDescription());
        self::assertSame(
            'package/quiqqer/products/bin/controls/products/ProductVariant',
            VariantParent::getTypeBackendPanel()
        );

        $Child = $Parent->createVariant();

        self::assertInstanceOf(VariantChild::class, $Child);
        self::assertSame($Parent->getId(), $Child->getParent()->getId());
        self::assertSame($Parent->getTitle(), $Child->getTitle());
        self::assertFalse(VariantChild::isTypeSelectable());
        self::assertSame(
            'package/quiqqer/products/bin/controls/products/ProductVariant',
            VariantChild::getTypeBackendPanel()
        );
        self::assertTrue($Parent->hasVariantId($Child->getId()));
        self::assertSame(1, $Parent->getVariants(['count' => true]));

        $Parent->setDefaultVariant($Child->getId());
        self::assertSame($Child->getId(), $Parent->getDefaultVariantId());
        self::assertSame($Child->getId(), $Parent->getDefaultVariant()->getId());

        $Parent->unsetDefaultVariant();
        self::assertFalse($Parent->getDefaultVariantId());
    }

    public function testVariantGenerationCreatesEveryRequestedAttributeCombination(): void
    {
        $Parent = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'generated-variants',
            75,
            VariantParent::class
        );
        self::assertInstanceOf(VariantParent::class, $Parent);

        $GenerationField = ProductTestHelper::runAsSystemUser(
            static fn() => Fields::createField([
                'id' => 1001,
                'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
                'name' => 'PHPUnit variant attribute',
                'publicField' => 1,
                'options' => [
                    'exclude_from_variant_generation' => false,
                    'entries' => [
                        ['valueId' => 'new', 'title' => ['de' => 'neu', 'en' => 'new']],
                        ['valueId' => 'used', 'title' => ['de' => 'gebraucht', 'en' => 'used']],
                        ['valueId' => 'refurbished', 'title' => ['de' => 'generalüberholt', 'en' => 'refurbished']]
                    ]
                ]
            ])
        );
        $Parent->addField($GenerationField);
        $Parent->generateVariants([
            [
                'fieldId' => $GenerationField->getId(),
                'values' => ['new', 'used']
            ]
        ]);

        $variants = $Parent->getVariants();
        self::assertCount(2, $variants);
        $hashes = [];
        $values = [];

        foreach ($variants as $Variant) {
            self::assertInstanceOf(VariantChild::class, $Variant);
            $hashes[] = $Variant->generateVariantHash();
            $values[] = $Variant->getField($GenerationField->getId())->getValue();
            self::assertSame($Variant->getId(), $Parent->getVariantByVariantHash(end($hashes))->getId());
        }

        self::assertCount(2, array_unique($hashes));
        sort($values);
        self::assertSame(['new', 'used'], $values);

        $Parent->generateVariants([
            [
                'fieldId' => $GenerationField->getId(),
                'values' => ['new', 'used', 'refurbished']
            ]
        ], VariantParent::GENERATION_TYPE_ADD);

        self::assertSame(3, $Parent->getVariants(['count' => true]));
    }

    public function testVariantGenerationBuildsLocalizedTitlesAndUrlsFromAttributes(): void
    {
        $Parent = ProductTestHelper::createProduct('variant-url-parent', 44, VariantParent::class);
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1802,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit variant material',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'steel', 'title' => ['de' => 'Stahl', 'en' => 'Steel']]
                ]
            ]
        ]));
        $Parent->addField($Attribute);
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $originalSetting = $Config->get('variants', 'useAttributesForVariantUrl');
        $Config->set('variants', 'useAttributesForVariantUrl', true);

        try {
            $Parent->generateVariants([[
                'fieldId' => $Attribute->getId(),
                'values' => ['steel']
            ]]);
        } finally {
            if ($originalSetting === false) {
                $Config->del('variants', 'useAttributesForVariantUrl');
            } else {
                $Config->set('variants', 'useAttributesForVariantUrl', $originalSetting);
            }
        }

        $Variant = $Parent->getVariants()[0];
        $titles = $Variant->getFieldValue(Fields::FIELD_TITLE);
        $urls = $Variant->getFieldValue(Fields::FIELD_URL);

        self::assertIsArray($titles);
        self::assertIsArray($urls);
        self::assertNotEmpty($titles);
        self::assertNotEmpty($urls);
        self::assertSame('steel', $Variant->getFieldValue($Attribute->getId()));
        foreach ($urls as $language => $url) {
            self::assertNotSame('', $titles[$language]);
            self::assertSame(QUI\Projects\Site\Utils::clearUrl($titles[$language]), $url);
        }
    }

    public function testFieldsAddedAfterVariantCreationArePropagatedAndRemoved(): void
    {
        $Parent = ProductTestHelper::createProduct('variant-field-propagation', 19, VariantParent::class);
        $Child = $Parent->createVariant();
        $Field = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1803,
            'type' => Fields::TYPE_INPUT,
            'name' => 'PHPUnit propagated variant field',
            'publicField' => 1
        ]));
        $Field->setOwnFieldStatus(true);

        $Parent->addField($Field);

        self::assertSame($Field->getId(), $Parent->getField($Field->getId())->getId());
        self::assertSame($Field->getId(), $Child->getField($Field->getId())->getId());
        self::assertContains($Field->getId(), $Parent->getAttribute('editableVariantFields'));
        self::assertContains($Field->getId(), $Parent->getAttribute('inheritedVariantFields'));

        $Parent->removeField($Field);

        self::assertFalse($Parent->hasField($Field->getId()));
        self::assertFalse($Child->hasField($Field->getId()));
        self::assertCount(1, $Parent->getVariants(['order' => 'c_date DESC']));
    }

    public function testDefaultVariantMustBeConfiguredBeforeItCanBeRead(): void
    {
        $Parent = ProductTestHelper::createProduct('variant-without-default', 10, VariantParent::class);

        $this->expectException(\QUI\ERP\Products\Product\Exception::class);
        $Parent->getDefaultVariant();
    }

    public function testExcludedGenerationFieldPreservesExistingVariants(): void
    {
        $Parent = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'excluded-generation-field',
            75,
            VariantParent::class
        );
        self::assertInstanceOf(VariantParent::class, $Parent);
        $ExistingVariant = $Parent->createVariant();
        $Condition = Fields::getField(Fields::FIELD_CONDITION);

        self::assertTrue((bool)$Condition->getOption('exclude_from_variant_generation'));

        $Parent->generateVariants([
            [
                'fieldId' => $Condition->getId(),
                'values' => ['new', 'used']
            ]
        ]);

        $variants = $Parent->getVariants();
        self::assertCount(1, $variants);
        self::assertSame($ExistingVariant->getId(), $variants[0]->getId());
    }

    public function testGeneratedVariantValuesAreReportedAsSelectable(): void
    {
        $Parent = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'available-variant-fields',
            25,
            VariantParent::class
        );
        $Attribute = ProductTestHelper::runAsSystemUser(
            static fn () => Fields::createField([
                'id' => 1801,
                'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
                'name' => 'PHPUnit available variant color',
                'publicField' => 1,
                'options' => [
                    'exclude_from_variant_generation' => false,
                    'entries' => [
                        ['valueId' => 'red', 'title' => ['de' => 'Rot', 'en' => 'Red']],
                        ['valueId' => 'blue', 'title' => ['de' => 'Blau', 'en' => 'Blue']]
                    ]
                ]
            ])
        );
        $Parent->addField($Attribute);
        $Parent->setAttribute('editableVariantFields', [Fields::FIELD_PRICE]);
        $Parent->setAttribute('inheritedVariantFields', [Fields::FIELD_PRICE]);
        QUI::getDataBaseConnection()->update(
            Tables::getProductTableName(),
            [
                'editableVariantFields' => json_encode([Fields::FIELD_PRICE], JSON_THROW_ON_ERROR),
                'inheritedVariantFields' => json_encode([Fields::FIELD_PRICE], JSON_THROW_ON_ERROR)
            ],
            ['id' => $Parent->getId()]
        );
        $Parent->generateVariants([[
            'fieldId' => $Attribute->getId(),
            'values' => ['red', 'blue']
        ]]);

        $available = $Parent->availableChildFields();
        self::assertSame(['red', 'blue'], $available['fields'][$Attribute->getId()]);
        self::assertCount(2, $available['hashes']);
        self::assertTrue($Parent->isFieldAvailable($Attribute->getId(), 'red'));
        self::assertFalse($Parent->isFieldAvailable($Attribute->getId(), 'green'));

        $variants = $Parent->getVariants(['order' => 'id DESC']);
        self::assertCount(2, $variants);
        self::assertSame(2, $Parent->getVariants(['count' => true]));
        self::assertCount(1, $Parent->getVariants(['limit' => 1, 'order' => 'id ASC']));

        foreach ($variants as $index => $Variant) {
            $Variant->getField(Fields::FIELD_PRICE)->setValue($index === 0 ? 60 : 20);
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Variant): void {
                $Variant->save($SystemUser);
                $Variant->activate($SystemUser);
            });
        }

        $activeFields = $Parent->availableActiveChildFields();
        self::assertSame(['red', 'blue'], $activeFields[$Attribute->getId()]);
        self::assertCount(2, $Parent->availableActiveFieldHashes());

        $productRows = QUI::getDataBaseConnection()->fetchAllAssociative(
            'SELECT id, fieldData FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName())
            . ' WHERE id IN (?, ?) ORDER BY id',
            [$variants[0]->getId(), $variants[1]->getId()]
        );
        $persistedPrices = [];
        foreach ($productRows as $row) {
            foreach (json_decode($row['fieldData'], true, flags: JSON_THROW_ON_ERROR) as $field) {
                if ((int)$field['id'] === Fields::FIELD_PRICE) {
                    $persistedPrices[] = (float)$field['value'];
                }
            }
        }
        sort($persistedPrices);
        self::assertSame([20.0, 60.0], $persistedPrices);

        $cachePrices = QUI::getDataBaseConnection()->fetchAllAssociative(
            'SELECT id, minPrice, maxPrice, active FROM '
            . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductCacheTableName())
            . ' WHERE id IN (?, ?) AND lang = ? ORDER BY minPrice',
            [$variants[0]->getId(), $variants[1]->getId(), 'de']
        );
        self::assertSame([20.0, 60.0], array_map('floatval', array_column($cachePrices, 'minPrice')));
        self::assertSame([20.0, 60.0], array_map('floatval', array_column($cachePrices, 'maxPrice')));
        self::assertSame([1, 1], array_map('intval', array_column($cachePrices, 'active')));

        $NettoUser = new TestUser(TestUser::TYPE_NETTO);
        self::assertSame(20.0, $Parent->getMinimumPrice($NettoUser)->value());
        self::assertSame(60.0, $Parent->getMaximumPrice($NettoUser)->value());
        $GrossUser = new TestUser(TestUser::TYPE_BRUTTO);
        self::assertSame(20.0, $Parent->getMinimumPrice($GrossUser)->value());
        self::assertSame(60.0, $Parent->getMaximumPrice($GrossUser)->value());

        $Parent->setDefaultVariant($variants[1]->getId());
        self::assertSame(20.0, $Parent->getCurrentPrice($NettoUser)->value());
        $Parent->unsetDefaultVariant();
        $Parent->setDefaultVariant(999999);
        self::assertFalse($Parent->getDefaultVariantId());

        try {
            $Parent->getVariantByVariantHash('missing-variant-hash');
            self::fail('An unknown variant hash must not resolve to a product.');
        } catch (\QUI\ERP\Products\Product\Exception $Exception) {
            self::assertSame(404, $Exception->getCode());
        }
    }

    public function testVariantChildDelegatesParentDataAndCreatesOwnMediaFolder(): void
    {
        $Parent = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'variant-child-delegation',
            45,
            VariantParent::class
        );
        $Parent->getField(Fields::FIELD_SHORT_DESC)->setValue([
            'de' => 'Parent description',
            'en' => 'Parent description'
        ]);
        $Parent->getField(Fields::FIELD_CONTENT)->setValue([
            'de' => 'Parent content',
            'en' => 'Parent content'
        ]);
        ProductTestHelper::runAsSystemUser(
            static fn ($SystemUser) => $Parent->save($SystemUser)
        );

        $Child = $Parent->createVariant();
        $Child->getField(Fields::FIELD_SHORT_DESC)->setValue(['de' => '', 'en' => '']);
        $Child->getField(Fields::FIELD_CONTENT)->setValue(['de' => '', 'en' => '']);

        self::assertSame('Parent description', $Child->getDescription());
        self::assertSame('Parent content', $Child->getContent());
        self::assertSame($Parent->getCategory()?->getId(), $Child->getCategory()?->getId());
        self::assertSame(
            array_map(static fn ($Category): int => $Category->getId(), $Parent->getCategories()),
            array_map(static fn ($Category): int => $Category->getId(), $Child->getCategories())
        );
        self::assertSame($Parent->availableChildFields(), $Child->availableChildFields());
        self::assertSame($Parent->availableActiveChildFields(), $Child->availableActiveChildFields());
        self::assertSame($Parent->availableActiveFieldHashes(), $Child->availableActiveFieldHashes());
        self::assertFalse($Child->hasOwnMediaFolder());

        $Folder = $Child->createOwnMediaFolder();

        self::assertTrue($Child->hasOwnMediaFolder());
        self::assertSame($Folder->getId(), $Child->getMediaFolder()->getId());
        self::assertSame($Folder->getUrl(), $Child->getFieldValue(Fields::FIELD_FOLDER));
        self::assertSame([], $Child->getImages());

        $hash = $Child->generateVariantHash();
        self::assertSame($Child->getId(), $Child->getVariantByVariantHash($hash)->getId());
    }

    public function testVariantChildRejectsParentWithWrongProductType(): void
    {
        $Product = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'invalid-variant-parent',
            45
        );
        $productData = QUI::getDataBaseConnection()->fetchAssociative(
            'SELECT * FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName())
            . ' WHERE id = ?',
            [$Product->getId()]
        );
        self::assertIsArray($productData);
        $productData['parent'] = $Product->getId();

        try {
            new VariantChild($Product->getId(), $productData);
            self::fail('A variant child must reject a parent that is not a variant parent.');
        } catch (\QUI\ERP\Products\Product\Exception $Exception) {
            self::assertSame(404, $Exception->getCode());
            self::assertSame($Product->getId(), $Exception->getContext()['parentId']);
            self::assertSame($Product->getId(), $Exception->getContext()['childId']);
        }
    }
}
