<?php

namespace QUITests\ERP\Products\Integration;

use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\MCP\Provider;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class McpCategoryToolsTest extends TestCase
{
    protected function tearDown(): void
    {
        Server::setRequestUser(null);
        parent::tearDown();
    }

    public function testCategoryLifecycleTools(): void
    {
        Server::setRequestUser(QUI::getUsers()->getSystemUser());
        $Builder = new Builder();
        (new Provider())->register($Builder);
        $tools = $Builder->getTools();
        $suffix = bin2hex(random_bytes(5));
        $parentTitle = 'MCP category parent ' . $suffix;
        $childTitle = 'MCP category child ' . $suffix;
        $updatedTitle = 'MCP category updated ' . $suffix;
        $parentId = null;
        $childId = null;

        try {
            $parent = $tools['quiqqer_products_categories_create']['callback'](
                ['de' => ['title' => $parentTitle, 'description' => 'Parent description']],
                0,
                'de'
            );
            $parentId = $parent['id'];
            self::assertSame(0, $parent['parentId']);
            self::assertTrue(Categories::existsCategory($parentId));

            $child = $tools['quiqqer_products_categories_create']['callback'](
                ['de' => ['title' => $childTitle]],
                $parentId,
                'de'
            );
            $childId = $child['id'];
            self::assertSame($parentId, $child['parentId']);

            $search = $tools['quiqqer_products_categories_search']['callback'](
                null,
                $parentId,
                'de',
                20,
                0,
                'ASC'
            );
            self::assertSame(1, $search['count']);
            self::assertSame($childId, $search['categories'][0]['id']);

            $updated = $tools['quiqqer_products_categories_update']['callback'](
                $childId,
                ['de' => ['title' => $updatedTitle, 'description' => 'Updated description']],
                0,
                'de'
            );
            self::assertSame(0, $updated['parentId']);

            $category = $tools['quiqqer_products_categories_get']['callback']($childId, 'de');
            self::assertSame($childId, $category['id']);
            self::assertArrayHasKey('fieldIds', $category);

            $deletedChild = $tools['quiqqer_products_categories_delete']['callback']($childId, 'de');
            self::assertTrue($deletedChild['deleted']);
            self::assertCategoryDeletedFromDatabase($childId);
            $childId = null;

            $deletedParent = $tools['quiqqer_products_categories_delete']['callback']($parentId, 'de');
            self::assertTrue($deletedParent['deleted']);
            self::assertCategoryDeletedFromDatabase($parentId);
            $parentId = null;
        } finally {
            if ($childId !== null && Categories::existsCategory($childId)) {
                ProductTestHelper::runAsSystemUser(static function () use ($childId): void {
                    Categories::getCategory($childId)->delete();
                });
            }

            if ($parentId !== null && Categories::existsCategory($parentId)) {
                ProductTestHelper::runAsSystemUser(static function () use ($parentId): void {
                    Categories::getCategory($parentId)->delete();
                });
            }
        }
    }

    public function testDeletingParentDeletesDescendants(): void
    {
        Server::setRequestUser(QUI::getUsers()->getSystemUser());
        $Builder = new Builder();
        (new Provider())->register($Builder);
        $tools = $Builder->getTools();
        $suffix = bin2hex(random_bytes(5));
        $parentId = null;

        try {
            $parent = $tools['quiqqer_products_categories_create']['callback'](
                ['de' => ['title' => 'MCP recursive parent ' . $suffix]],
                0,
                'de'
            );
            $parentId = $parent['id'];
            $child = $tools['quiqqer_products_categories_create']['callback'](
                ['de' => ['title' => 'MCP recursive child ' . $suffix]],
                $parentId,
                'de'
            );

            $result = $tools['quiqqer_products_categories_delete']['callback']($parentId, 'de');

            self::assertTrue($result['deleted']);
            self::assertCategoryDeletedFromDatabase($parentId);
            self::assertCategoryDeletedFromDatabase($child['id']);
            $parentId = null;
        } finally {
            if ($parentId !== null && Categories::existsCategory($parentId)) {
                ProductTestHelper::runAsSystemUser(static function () use ($parentId): void {
                    Categories::getCategory($parentId)->delete();
                });
            }
        }
    }

    private static function assertCategoryDeletedFromDatabase(int $categoryId): void
    {
        $count = QUI::getDataBaseConnection()
            ->createQueryBuilder()
            ->select('COUNT(id)')
            ->from(Tables::getCategoryTableName())
            ->where('id = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->executeQuery()
            ->fetchOne();

        self::assertSame(0, (int)$count);
    }
}
