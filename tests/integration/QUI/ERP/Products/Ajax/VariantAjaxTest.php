<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\Rewrite;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class VariantAjaxTest extends AjaxTestCase
{
    private ?Rewrite $originalRewrite;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRewrite = QUI::$Rewrite;
        $Rewrite = $this->createMock(Rewrite::class);
        $Rewrite->method('getProject')->willReturn(ProductTestHelper::getProject());
        $Rewrite->method('getSite')->willReturn(ProductTestHelper::getCategorySite());
        QUI::$Rewrite = $Rewrite;
    }

    protected function tearDown(): void
    {
        QUI::$Rewrite = $this->originalRewrite;
        parent::tearDown();
    }

    public function testVariantGridReturnsPersistedChildrenFieldsAndDefaultSelection(): void
    {
        $Parent = ProductTestHelper::createProduct('ajax-variant-parent', 29.5, VariantParent::class);
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1701,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit Ajax variant color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'red', 'title' => ['de' => 'Rot', 'en' => 'Red']],
                    ['valueId' => 'blue', 'title' => ['de' => 'Blau', 'en' => 'Blue']]
                ]
            ]
        ]));
        $Category = ProductTestHelper::getCategory();
        $Category->addField($Attribute);
        ProductTestHelper::runAsSystemUser(static function () use ($Category): void {
            $Category->save();
        });
        $Parent->addField($Attribute);
        $Parent->generateVariants([[
            'fieldId' => $Attribute->getId(),
            'values' => ['red', 'blue']
        ]]);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Parent): void {
            $Parent->save($SystemUser);
        });
        $children = $Parent->getVariants();
        self::assertCount(2, $children);
        $Parent->setDefaultVariant($children[0]->getId());
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Parent): void {
            $Parent->save($SystemUser);
        });

        $result = $this->invokeEndpoint(
            'products/variant/getVariants.php',
            'package_quiqqer_products_ajax_products_variant_getVariants',
            $Parent->getId(),
            json_encode(['page' => 1, 'perPage' => 20], JSON_THROW_ON_ERROR)
        );

        self::assertSame(1, $result['page']);
        self::assertSame(2, $result['total']);
        self::assertCount(2, $result['data']);
        self::assertSame(
            [$children[0]->getId(), $children[1]->getId()],
            array_map('intval', array_column($result['data'], 'id'))
        );
        $defaultById = array_column($result['data'], 'defaultVariant', 'id');
        self::assertSame(1, $defaultById[$children[0]->getId()]);
        self::assertSame(1, array_sum($defaultById));
        self::assertNotSame('', $result['data'][0]['price_netto_display']);

        $firstFields = array_column($result['data'][0]['fields'], null, 'id');
        self::assertSame(29.5, (float)$firstFields[Fields::FIELD_PRICE]['value']);
        self::assertArrayHasKey($Attribute->getId(), $firstFields);
        self::assertContains($firstFields[$Attribute->getId()]['title'], ['Rot', 'Blau'], true);
    }

    public function testVariantGridRejectsOrdinaryProducts(): void
    {
        $Product = ProductTestHelper::createProduct('ajax-no-variant', 10.0);

        self::assertSame([], $this->invokeEndpoint(
            'products/variant/getVariants.php',
            'package_quiqqer_products_ajax_products_variant_getVariants',
            $Product->getId(),
            json_encode(['page' => 3], JSON_THROW_ON_ERROR)
        ));
    }

    public function testFrontendVariantEndpointResolvesSelectedActiveChild(): void
    {
        $Parent = ProductTestHelper::createProduct('ajax-frontend-variant', 33.0, VariantParent::class);
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1702,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit Ajax frontend variant color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'red', 'title' => ['de' => 'Rot', 'en' => 'Red']],
                    ['valueId' => 'blue', 'title' => ['de' => 'Blau', 'en' => 'Blue']]
                ]
            ]
        ]));
        $Category = ProductTestHelper::getCategory();
        $Category->addField($Attribute);
        ProductTestHelper::runAsSystemUser(static function () use ($Category): void {
            $Category->save();
        });
        $Parent->addField($Attribute);
        $Parent->generateVariants([[
            'fieldId' => $Attribute->getId(),
            'values' => ['red', 'blue']
        ]]);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Parent): void {
            $Parent->save($SystemUser);
        });
        $children = $Parent->getVariants();

        foreach ($children as $Child) {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Child): void {
                $Child->activate($SystemUser);
            });
        }

        $expectedChild = $children[0];

        $result = $this->invokeEndpoint(
            'products/frontend/getVariant.php',
            'package_quiqqer_products_ajax_products_frontend_getVariant',
            $Parent->getId(),
            json_encode([[
                Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES => $expectedChild->getId()
            ]], JSON_THROW_ON_ERROR),
            false
        );

        self::assertSame($expectedChild->getId(), $result['variantId']);
        self::assertSame('ajax-frontend-variant', $result['title']);
        self::assertSame(ProductTestHelper::getCategory()->getId(), $result['category']);
        self::assertFalse($result['isVariantParent']);
        self::assertStringContainsString('data-productid="' . $expectedChild->getId() . '"', $result['control']);
        self::assertIsArray($result['fieldHashes']);
        self::assertNotEmpty($result['availableHashes']);
        self::assertArrayHasKey('css', $result);
        self::assertArrayHasKey('seoTitle', $result);
        self::assertArrayHasKey('seoDescription', $result);

        $redChild = null;
        $blueChild = null;
        foreach ($children as $Child) {
            if ($Child->getField($Attribute->getId())->getValue() === 'red') {
                $redChild = $Child;
            } else {
                $blueChild = $Child;
            }
        }
        self::assertNotNull($redChild);
        self::assertNotNull($blueChild);

        $resolvedByHash = $this->invokeEndpoint(
            'products/frontend/getVariant.php',
            'package_quiqqer_products_ajax_products_frontend_getVariant',
            $Parent->getId(),
            json_encode([[$Attribute->getId() => 'red']], JSON_THROW_ON_ERROR),
            false
        );
        self::assertSame($redChild->getId(), $resolvedByHash['variantId']);
        self::assertFalse($resolvedByHash['isVariantParent']);

        $switchedFromChild = $this->invokeEndpoint(
            'products/frontend/getVariant.php',
            'package_quiqqer_products_ajax_products_frontend_getVariant',
            $redChild->getId(),
            json_encode([[
                Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES => $blueChild->getId()
            ]], JSON_THROW_ON_ERROR),
            true
        );
        self::assertSame($blueChild->getId(), $switchedFromChild['variantId']);

        $resolvedById = $this->invokeEndpoint(
            'products/frontend/getVariantByUrl.php',
            'package_quiqqer_products_ajax_products_frontend_getVariantByUrl',
            $Parent->getId(),
            '',
            $redChild->getId()
        );
        self::assertSame($redChild->getId(), $resolvedById['productId']);
        self::assertSame('red', $resolvedById['fields'][$Attribute->getId()]);

        $fallbackToParent = $this->invokeEndpoint(
            'products/frontend/getVariantByUrl.php',
            'package_quiqqer_products_ajax_products_frontend_getVariantByUrl',
            $blueChild->getId(),
            '/unknown-variant-url/',
            false
        );
        self::assertSame($Parent->getId(), $fallbackToParent['productId']);
        self::assertArrayHasKey($Attribute->getId(), $fallbackToParent['fields']);

        self::assertSame([], $this->invokeEndpoint(
            'products/frontend/getVariantByUrl.php',
            'package_quiqqer_products_ajax_products_frontend_getVariantByUrl',
            $Parent->getId(),
            '',
            999999
        ));

        $Ordinary = ProductTestHelper::createProduct('ajax-ordinary-url-lookup');
        self::assertSame([], $this->invokeEndpoint(
            'products/frontend/getVariantByUrl.php',
            'package_quiqqer_products_ajax_products_frontend_getVariantByUrl',
            $Ordinary->getId(),
            '/not-a-variant/',
            false
        ));
    }

    public function testEditableInheritedFieldListUsesGlobalSettingsWithoutProduct(): void
    {
        $result = $this->invokeEndpointAsAdmin(
            'products/variant/getEditableInheritedFieldList.php',
            'package_quiqqer_products_ajax_products_variant_getEditableInheritedFieldList',
            0,
            'invalid-json'
        );

        self::assertSame(
            array_map(static fn ($Field): int => $Field->getId(), Products::getGlobalEditableVariantFields()),
            array_map('intval', $result['editable'])
        );
        self::assertSame(
            array_map(static fn ($Field): int => $Field->getId(), Products::getGlobalInheritedVariantFields()),
            array_map('intval', $result['inherited'])
        );
        self::assertSame(1, $result['page']);
        self::assertSame(count(Fields::getFields()), $result['total']);
        self::assertLessThanOrEqual(20, count($result['fields']));
        $fieldIds = array_values(array_map('intval', array_column($result['fields'], 'id')));
        $sortedFieldIds = $fieldIds;
        sort($sortedFieldIds);
        self::assertSame($sortedFieldIds, $fieldIds);
    }

    public function testEditableInheritedFieldListUsesParentOverridesForVariantChild(): void
    {
        $Parent = ProductTestHelper::createProduct('ajax-editable-fields-parent', 17.0, VariantParent::class);
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1703,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit Ajax editable fields color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'green', 'title' => ['de' => 'Grün', 'en' => 'Green']]
                ]
            ]
        ]));
        $Category = ProductTestHelper::getCategory();
        $Category->addField($Attribute);
        ProductTestHelper::runAsSystemUser(static function () use ($Category): void {
            $Category->save();
        });
        $Parent->addField($Attribute);
        $Parent->setAttribute('editableVariantFields', [Fields::FIELD_PRICE]);
        $Parent->setAttribute('inheritedVariantFields', [Fields::FIELD_TITLE]);
        $Parent->generateVariants([[
            'fieldId' => $Attribute->getId(),
            'values' => ['green']
        ]]);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Parent): void {
            $Parent->save($SystemUser);
        });
        $Child = $Parent->getVariants()[0];

        $result = $this->invokeEndpointAsAdmin(
            'products/variant/getEditableInheritedFieldList.php',
            'package_quiqqer_products_ajax_products_variant_getEditableInheritedFieldList',
            $Child->getId(),
            json_encode([
                'sortOn' => 'priority',
                'sortBy' => 'DESC',
                'perPage' => 2,
                'page' => 1
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame([Fields::FIELD_PRICE], array_map('intval', $result['editable']));
        self::assertSame([Fields::FIELD_TITLE], array_map('intval', $result['inherited']));
        self::assertSame(1, $result['page']);
        self::assertSame(2, count($result['fields']));
        self::assertGreaterThan(2, $result['total']);
        self::assertSame(0, (int)$result['fields'][0]['priority']);
        self::assertGreaterThan(0, (int)$result['fields'][1]['priority']);
    }

    public function testVariantAdministrationEndpointsPersistParentSettingsAndFolderOwnership(): void
    {
        $Parent = ProductTestHelper::createProduct('ajax-admin-variant', 21.0, VariantParent::class);
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1704,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit Ajax administration variant color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'yellow', 'title' => ['de' => 'Gelb', 'en' => 'Yellow']]
                ]
            ]
        ]));
        $Category = ProductTestHelper::getCategory();
        $Category->addField($Attribute);
        ProductTestHelper::runAsSystemUser(static function () use ($Category): void {
            $Category->save();
        });
        $Parent->addField($Attribute);
        $Parent->generateVariants([[
            'fieldId' => $Attribute->getId(),
            'values' => ['yellow']
        ]]);
        ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Parent): void {
            $Parent->save($SystemUser);
        });
        $Child = $Parent->getVariants()[0];
        $Ordinary = ProductTestHelper::createProduct('ajax-admin-ordinary');

        self::assertSame($Parent->getId(), $this->invokeEndpointAsAdmin(
            'products/variant/getParent.php',
            'package_quiqqer_products_ajax_products_variant_getParent',
            $Parent->getId()
        ));
        self::assertSame($Parent->getId(), $this->invokeEndpointAsAdmin(
            'products/variant/getParent.php',
            'package_quiqqer_products_ajax_products_variant_getParent',
            $Child->getId()
        ));
        self::assertFalse($this->invokeEndpointAsAdmin(
            'products/variant/getParent.php',
            'package_quiqqer_products_ajax_products_variant_getParent',
            $Ordinary->getId()
        ));

        $availableFields = $this->invokeEndpointAsAdmin(
            'products/variant/getAvailableVariantFields.php',
            'package_quiqqer_products_ajax_products_variant_getAvailableVariantFields'
        );
        self::assertContains($Attribute->getId(), array_map('intval', array_column($availableFields, 'id')));

        $variantFields = $this->invokeEndpointAsAdmin(
            'products/variant/getVariantFields.php',
            'package_quiqqer_products_ajax_products_variant_getVariantFields',
            $Parent->getId()
        );
        self::assertContains($Attribute->getId(), array_map('intval', array_column($variantFields, 'id')));
        self::assertSame(
            [Fields::TYPE_ATTRIBUTE_GROUPS],
            array_values(array_unique(array_column($variantFields, 'type')))
        );

        self::assertTrue($this->invokeEndpointAsAdmin(
            'products/variant/hasOwnFolder.php',
            'package_quiqqer_products_ajax_products_variant_hasOwnFolder',
            $Ordinary->getId()
        ));
        self::assertFalse($this->invokeEndpointAsAdmin(
            'products/variant/hasOwnFolder.php',
            'package_quiqqer_products_ajax_products_variant_hasOwnFolder',
            $Child->getId()
        ));

        self::assertNull($this->invokeEndpointAsAdmin(
            'products/variant/changeOwnFolderStatus.php',
            'package_quiqqer_products_ajax_products_variant_changeOwnFolderStatus',
            $Child->getId()
        ));
        Products::cleanProductInstanceMemCache($Child->getId());
        self::assertTrue($this->invokeEndpointAsAdmin(
            'products/variant/hasOwnFolder.php',
            'package_quiqqer_products_ajax_products_variant_hasOwnFolder',
            $Child->getId()
        ));

        self::assertNull($this->invokeEndpointAsAdmin(
            'products/variant/setDefaultVariant.php',
            'package_quiqqer_products_ajax_products_variant_setDefaultVariant',
            $Parent->getId(),
            $Child->getId()
        ));
        Products::cleanProductInstanceMemCache($Parent->getId());
        self::assertSame(
            $Child->getId(),
            Products::getNewProductInstance($Parent->getId())->getDefaultVariantId()
        );

        $this->invokeEndpointAsAdmin(
            'products/variant/saveEditableInheritedFields.php',
            'package_quiqqer_products_ajax_products_variant_saveEditableInheritedFields',
            $Child->getId(),
            json_encode([Fields::FIELD_PRICE], JSON_THROW_ON_ERROR),
            json_encode([Fields::FIELD_TITLE], JSON_THROW_ON_ERROR)
        );
        Products::cleanProductInstanceMemCache($Parent->getId());
        $ReloadedParent = Products::getNewProductInstance($Parent->getId());
        self::assertSame([Fields::FIELD_PRICE], $ReloadedParent->getAttribute('editableVariantFields'));
        self::assertSame([Fields::FIELD_TITLE], $ReloadedParent->getAttribute('inheritedVariantFields'));

        $this->invokeEndpointAsAdmin(
            'products/variant/resetEditableInheritedFields.php',
            'package_quiqqer_products_ajax_products_variant_resetEditableInheritedFields',
            $Child->getId()
        );
        Products::cleanProductInstanceMemCache($Parent->getId());
        $ReloadedParent = Products::getNewProductInstance($Parent->getId());
        self::assertFalse($ReloadedParent->getAttribute('editableVariantFields'));
        self::assertFalse($ReloadedParent->getAttribute('inheritedVariantFields'));

        $this->invokeEndpointAsAdmin(
            'products/variant/setDefaultVariant.php',
            'package_quiqqer_products_ajax_products_variant_setDefaultVariant',
            $Parent->getId(),
            0
        );
        Products::cleanProductInstanceMemCache($Parent->getId());
        self::assertFalse(Products::getNewProductInstance($Parent->getId())->getDefaultVariantId());
    }

    public function testVariantGenerationEndpointsPersistMassUpdatesAndLifecycleStates(): void
    {
        $Parent = ProductTestHelper::createProduct('ajax-generate-variant', 26.0, VariantParent::class);
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1705,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit Ajax generated variant color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'red', 'title' => ['de' => 'Rot', 'en' => 'Red']],
                    ['valueId' => 'blue', 'title' => ['de' => 'Blau', 'en' => 'Blue']]
                ]
            ]
        ]));
        $Category = ProductTestHelper::getCategory();
        $Category->addField($Attribute);
        ProductTestHelper::runAsSystemUser(static function () use ($Category, $Parent, $Attribute): void {
            $Category->save();
            $Parent->addField($Attribute);
            $Parent->save();
        });

        self::assertNull($this->invokeEndpointAsAdmin(
            'products/variant/generate/generate.php',
            'package_quiqqer_products_ajax_products_variant_generate_generate',
            $Parent->getId(),
            json_encode([
                ['fieldId' => $Attribute->getId(), 'values' => ['red', 'blue']],
                ['fieldId' => 0, 'values' => ['ignored']]
            ], JSON_THROW_ON_ERROR),
            'reset'
        ));
        Products::cleanProductInstanceMemCache($Parent->getId());
        $variants = Products::getNewProductInstance($Parent->getId())->getVariants();
        self::assertCount(2, $variants);
        $variantIds = array_map(static fn ($Variant): int => $Variant->getId(), $variants);

        self::assertNull($this->invokeEndpointAsAdmin(
            'products/variant/generate/activate.php',
            'package_quiqqer_products_ajax_products_variant_generate_activate',
            json_encode($variantIds, JSON_THROW_ON_ERROR)
        ));
        foreach ($variantIds as $variantId) {
            Products::cleanProductInstanceMemCache($variantId);
            self::assertTrue(Products::getNewProductInstance($variantId)->isActive());
        }

        $Messages = new ReflectionProperty(QUI\Messages\Handler::class, 'messages');
        $originalMessages = $Messages->getValue(QUI::getMessagesHandler());
        $Messages->setValue(QUI::getMessagesHandler(), []);

        try {
            self::assertNull($this->invokeEndpointAsAdmin(
                'products/variant/massProcessing.php',
                'package_quiqqer_products_ajax_products_variant_massProcessing',
                json_encode($variantIds, JSON_THROW_ON_ERROR),
                Fields::FIELD_PRICE,
                json_encode(37.25, JSON_THROW_ON_ERROR)
            ));
            $messages = $Messages->getValue(QUI::getMessagesHandler());
            self::assertCount(1, $messages);
            self::assertContainsOnlyInstancesOf(QUI\Messages\Success::class, $messages);
        } finally {
            $Messages->setValue(QUI::getMessagesHandler(), $originalMessages);
        }
        foreach ($variantIds as $variantId) {
            Products::cleanProductInstanceMemCache($variantId);
            self::assertSame(
                37.25,
                Products::getNewProductInstance($variantId)->getFieldValue(Fields::FIELD_PRICE)
            );
        }

        self::assertNull($this->invokeEndpointAsAdmin(
            'products/variant/generate/deactivate.php',
            'package_quiqqer_products_ajax_products_variant_generate_deactivate',
            json_encode($variantIds, JSON_THROW_ON_ERROR)
        ));
        foreach ($variantIds as $variantId) {
            Products::cleanProductInstanceMemCache($variantId);
            self::assertFalse(Products::getNewProductInstance($variantId)->isActive());
        }

        $createdVariantId = $this->invokeEndpointAsAdmin(
            'products/variant/generate/create.php',
            'package_quiqqer_products_ajax_products_variant_generate_create',
            $Parent->getId(),
            json_encode([$Attribute->getId() => 'red'], JSON_THROW_ON_ERROR)
        );
        self::assertIsInt($createdVariantId);
        self::assertTrue(Products::existsProduct($createdVariantId));
        self::assertSame(
            'red',
            Products::getNewProductInstance($createdVariantId)->getFieldValue($Attribute->getId())
        );
        $variantIds[] = $createdVariantId;

        self::assertNull($this->invokeEndpointAsAdmin(
            'products/variant/generate/delete.php',
            'package_quiqqer_products_ajax_products_variant_generate_delete',
            json_encode($variantIds, JSON_THROW_ON_ERROR)
        ));
        foreach ($variantIds as $variantId) {
            self::assertFalse(Products::existsProduct($variantId));
        }
    }
}
