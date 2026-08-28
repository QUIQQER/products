<?php

namespace QUITests\ERP\Products\Integration;

use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\MCP\Provider;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class McpProductToolsTest extends TestCase
{
    protected function tearDown(): void
    {
        Server::setRequestUser(null);
        parent::tearDown();
    }

    public function testReadSearchPriceAndPermissionTools(): void
    {
        $SystemUser = QUI::getUsers()->getSystemUser();
        Server::setRequestUser($SystemUser);
        $Product = ProductTestHelper::createProduct('mcp-product-tool', 37.5);
        $Builder = new Builder();
        (new Provider())->register($Builder);
        $tools = $Builder->getTools();

        $product = $tools['quiqqer_products_get']['callback']($Product->getId(), 'de', false);
        self::assertSame($Product->getId(), $product['id']);
        self::assertSame('mcp-product-tool', $product['title']);
        self::assertArrayNotHasKey('price', $product);

        foreach ($product['fields'] as $field) {
            self::assertNotSame(Fields::TYPE_PRICE, $field['type']);
        }

        $pricedProduct = $tools['quiqqer_products_get']['callback']($Product->getId(), 'de', true);
        self::assertSame(37.5, $pricedProduct['price']['value']);

        $search = $tools['quiqqer_products_search']['callback'](
            'mcp-product-tool',
            'de',
            null,
            null,
            null,
            null,
            20,
            0,
            'id',
            'DESC',
            false
        );
        self::assertSame(1, $search['count']);
        self::assertSame($Product->getId(), $search['products'][0]['id']);
        self::assertArrayNotHasKey('fields', $search['products'][0]);

        $updated = $tools['quiqqer_products_permissions_update']['callback'](
            $Product->getId(),
            ['viewable' => 'g1', 'buyable' => 'u2']
        );
        self::assertSame('g1', $updated['permissions']['viewable']);
        self::assertSame('u2', $updated['permissions']['buyable']);

        Products::cleanProductInstanceMemCache($Product->getId());
        $permissions = $tools['quiqqer_products_permissions_get']['callback']($Product->getId());
        self::assertSame($updated, $permissions);
    }

    public function testProductMutationTools(): void
    {
        Server::setRequestUser(QUI::getUsers()->getSystemUser());
        $Builder = new Builder();
        (new Provider())->register($Builder);
        $tools = $Builder->getTools();
        $categoryId = ProductTestHelper::getCategory()->getId();

        $created = $tools['quiqqer_products_create']['callback'](
            [$categoryId],
            [
                ['id' => Fields::FIELD_TITLE, 'value' => ['de' => 'MCP created product']],
                ['id' => Fields::FIELD_PRODUCT_NO, 'value' => 'mcp-created-product'],
                ['id' => Fields::FIELD_PRICE, 'value' => 10]
            ],
            null,
            null,
            $categoryId,
            'de'
        );
        $productId = $created['id'];
        self::assertSame('MCP created product', $created['title']);
        self::assertSame($categoryId, $created['mainCategoryId']);

        $updated = $tools['quiqqer_products_update']['callback'](
            $productId,
            [['id' => Fields::FIELD_TITLE, 'value' => ['de' => 'MCP updated product']]],
            null,
            null,
            'de'
        );
        self::assertSame('MCP updated product', $updated['title']);

        $activated = $tools['quiqqer_products_activate']['callback']($productId, 'de');
        self::assertTrue($activated['active']);
        $deactivated = $tools['quiqqer_products_deactivate']['callback']($productId, 'de');
        self::assertFalse($deactivated['active']);

        $copy = $tools['quiqqer_products_copy']['callback']($productId, 'de');
        self::assertNotSame($productId, $copy['id']);
        self::assertSame('MCP updated product', $copy['title']);

        self::assertSame(
            ['productId' => $copy['id'], 'deleted' => true],
            $tools['quiqqer_products_delete']['callback']($copy['id'])
        );
        self::assertSame(
            ['productId' => $productId, 'deleted' => true],
            $tools['quiqqer_products_delete']['callback']($productId)
        );
    }
}
