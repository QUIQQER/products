<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Category\SearchCategories
 */

namespace QUI\ERP\Products\MCP\Category;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Utils\Tables;
use Throwable;

class SearchCategories extends AbstractCategoryTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string | null $query = null,
                int | null $parentId = null,
                string | null $lang = null,
                int | null $limit = null,
                int | null $offset = null,
                string | null $sortDirection = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    return self::search($query, $parentId, $lang, $limit, $offset, $sortDirection);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_categories_search',
            description: 'Searches product categories by localized title or description.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Localized title or description search text.'],
                    'parentId' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'description' => 'Return only direct children of this category ID.'
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Language used for returned localized values.'],
                    'limit' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'default' => 0, 'minimum' => 0],
                    'sortDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'ASC']
                ]
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function search(
        ?string $query,
        ?int $parentId,
        ?string $lang,
        ?int $limit,
        ?int $offset,
        ?string $sortDirection
    ): array {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $table = $Platform->quoteSingleIdentifier(Tables::getCategoryTableName());
        $limit = self::sanitizeLimit($limit);
        $offset = self::sanitizeOffset($offset);
        $sortDirection = $sortDirection === 'DESC' ? 'DESC' : 'ASC';
        $QueryBuilder = $Connection->createQueryBuilder();
        self::applyQuery($QueryBuilder, $table, $query, $parentId);
        $rows = $QueryBuilder
            ->select('id')
            ->orderBy('id', $sortDirection)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
        $CountQuery = $Connection->createQueryBuilder();
        self::applyQuery($CountQuery, $table, $query, $parentId);
        $count = (int)$CountQuery->select('COUNT(id)')->executeQuery()->fetchOne();
        $categories = [];

        foreach ($rows as $row) {
            try {
                $categories[] = self::parseCategory(self::getCategory((int)$row['id']), $lang);
            } catch (Throwable $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return [
            'count' => $count,
            'limit' => $limit,
            'offset' => $offset,
            'categories' => $categories
        ];
    }

    protected static function applyQuery(
        \Doctrine\DBAL\Query\QueryBuilder $QueryBuilder,
        string $table,
        ?string $query,
        ?int $parentId
    ): void {
        $QueryBuilder->from($table);

        if ($query !== null && $query !== '') {
            $QueryBuilder
                ->andWhere($QueryBuilder->expr()->or(
                    'title_cache LIKE :query',
                    'description_cache LIKE :query'
                ))
                ->setParameter('query', '%' . $query . '%');
        }

        if ($parentId !== null) {
            $QueryBuilder->andWhere('parentId = :parentId')->setParameter('parentId', $parentId);
        }
    }
}
