<?php

namespace QUITests\ERP\Products\Integration;

use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\MCP\Provider;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class McpVariantToolsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    protected function tearDown(): void
    {
        Server::setRequestUser(null);
        parent::tearDown();
    }

    public function testProductTypeAndVariantManagementTools(): void
    {
        Server::setRequestUser(QUI::getUsers()->getSystemUser());
        $Builder = new Builder();
        (new Provider())->register($Builder);
        $tools = $Builder->getTools();
        $types = $tools['quiqqer_products_product_types_list']['callback']('de');
        self::assertContains(VariantParent::class, array_column($types['productTypes'], 'type'));

        $Parent = ProductTestHelper::createProduct('mcp-variant-parent', 35, VariantParent::class);
        self::assertInstanceOf(VariantParent::class, $Parent);
        $inheritance = $tools['quiqqer_products_variants_inheritance_get']['callback']($Parent->getId(), 'de');
        self::assertSame($Parent->getId(), $inheritance['variantParentId']);

        $inheritance = $tools['quiqqer_products_variants_inheritance_update']['callback'](
            $Parent->getId(),
            [Fields::FIELD_PRICE],
            [Fields::FIELD_TITLE],
            false,
            'de'
        );
        self::assertSame([Fields::FIELD_PRICE], $inheritance['editableFieldIds']);
        self::assertSame([Fields::FIELD_TITLE], $inheritance['inheritedFieldIds']);
        self::assertFalse($inheritance['usesGlobalEditableFields']);
        self::assertFalse($inheritance['usesGlobalInheritedFields']);

        $created = $tools['quiqqer_products_variants_create']['callback'](
            $Parent->getId(),
            null,
            'de',
            false
        );
        $firstVariantId = $created['id'];
        self::assertSame($Parent->getId(), $created['variantParentId']);
        self::assertFalse($created['defaultVariant']);

        $default = $tools['quiqqer_products_variants_default_set']['callback'](
            $Parent->getId(),
            $firstVariantId
        );
        self::assertSame($firstVariantId, $default['defaultVariantId']);

        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => self::findUnusedCustomFieldId(),
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'MCP variant color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'red', 'title' => ['de' => 'Rot', 'en' => 'Red']],
                    ['valueId' => 'blue', 'title' => ['de' => 'Blau', 'en' => 'Blue']],
                    ['valueId' => 'green', 'title' => ['de' => 'Grün', 'en' => 'Green']]
                ]
            ]
        ]));

        $generated = $tools['quiqqer_products_variants_generate']['callback'](
            $Parent->getId(),
            [['fieldId' => $Attribute->getId(), 'values' => ['red', 'blue']]],
            'add'
        );
        self::assertSame(2, $generated['combinationCount']);
        self::assertCount(2, $generated['createdVariantIds']);
        self::assertSame(3, $generated['variantCount']);

        $activated = $tools['quiqqer_products_variants_bulk_action']['callback'](
            $Parent->getId(),
            $generated['createdVariantIds'],
            'activate'
        );
        self::assertSame($generated['createdVariantIds'], $activated['processedVariantIds']);
        self::assertSame([], $activated['failures']);

        foreach ($generated['createdVariantIds'] as $variantId) {
            Products::cleanProductInstanceMemCache($variantId);
            self::assertTrue(Products::getNewProductInstance($variantId)->isActive());
        }

        $deactivated = $tools['quiqqer_products_variants_bulk_action']['callback'](
            $Parent->getId(),
            $generated['createdVariantIds'],
            'deactivate'
        );
        self::assertSame($generated['createdVariantIds'], $deactivated['processedVariantIds']);
        self::assertSame([], $deactivated['failures']);

        $reset = $tools['quiqqer_products_variants_generate']['callback'](
            $Parent->getId(),
            [['fieldId' => $Attribute->getId(), 'values' => ['green']]],
            'reset'
        );
        self::assertSame(1, $reset['combinationCount']);
        self::assertCount(3, $reset['deletedVariantIds']);
        self::assertCount(1, $reset['createdVariantIds']);
        self::assertSame(1, $reset['variantCount']);
        $remainingVariantId = $reset['createdVariantIds'][0];

        $list = $tools['quiqqer_products_variants_list']['callback'](
            $remainingVariantId,
            null,
            null,
            'de',
            20,
            0,
            'ASC',
            true,
            false
        );
        self::assertSame($Parent->getId(), $list['variantParentId']);
        self::assertSame(1, $list['count']);
        self::assertSame($remainingVariantId, $list['variants'][0]['id']);
        self::assertArrayHasKey('fields', $list['variants'][0]);

        $default = $tools['quiqqer_products_variants_default_set']['callback'](
            $Parent->getId(),
            $remainingVariantId
        );
        self::assertSame($remainingVariantId, $default['defaultVariantId']);

        $default = $tools['quiqqer_products_variants_default_set']['callback']($Parent->getId(), 0);
        self::assertNull($default['defaultVariantId']);

        $deleted = $tools['quiqqer_products_variants_bulk_action']['callback'](
            $Parent->getId(),
            [$remainingVariantId],
            'delete'
        );
        self::assertSame([$remainingVariantId], $deleted['processedVariantIds']);
        self::assertSame([], $deleted['failures']);
        self::assertFalse(Products::existsProduct($remainingVariantId));

        $resetInheritance = $tools['quiqqer_products_variants_inheritance_update']['callback'](
            $Parent->getId(),
            null,
            null,
            true,
            'de'
        );
        self::assertTrue($resetInheritance['usesGlobalEditableFields']);
        self::assertTrue($resetInheritance['usesGlobalInheritedFields']);
    }

    private static function findUnusedCustomFieldId(): int
    {
        for ($fieldId = 9000; $fieldId < 10000; $fieldId++) {
            $count = QUI::getDataBaseConnection()
                ->createQueryBuilder()
                ->select('COUNT(id)')
                ->from(Tables::getFieldTableName())
                ->where('id = :fieldId')
                ->setParameter('fieldId', $fieldId)
                ->executeQuery()
                ->fetchOne();

            if ((int)$count === 0) {
                return $fieldId;
            }
        }

        self::fail('No free custom product-field ID is available for the MCP variant test.');
    }
}
