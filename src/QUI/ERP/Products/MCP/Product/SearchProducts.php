<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Product\SearchProducts
 */

namespace QUI\ERP\Products\MCP\Product;

use Doctrine\DBAL\Query\QueryBuilder;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\MCP\AbstractTool;
use QUI\ERP\Products\Utils\Tables;
use Throwable;

class SearchProducts extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string | null $query = null,
                string | null $lang = null,
                bool | null $active = null,
                int | null $categoryId = null,
                string | null $productType = null,
                int | null $parentId = null,
                int | null $limit = null,
                int | null $offset = null,
                string | null $sortBy = null,
                string | null $sortDirection = null,
                bool | null $includePrices = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    if ($includePrices === true) {
                        self::checkPermission('product.view.prices');
                    }

                    return self::search(
                        $query,
                        $lang,
                        $active,
                        $categoryId,
                        $productType,
                        $parentId,
                        $limit,
                        $offset,
                        $sortBy,
                        $sortDirection,
                        $includePrices === true
                    );
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_search',
            description: 'Searches products by ID, product number, title and description.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Free-text query or exact numeric product ID.'],
                    'lang' => ['type' => 'string', 'description' => 'Language of the product search cache.'],
                    'active' => ['type' => 'boolean', 'description' => 'Filter by activation status.'],
                    'categoryId' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Existing category ID.'],
                    'productType' => ['type' => 'string', 'description' => 'Fully qualified product type class.'],
                    'parentId' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Parent product ID; 0 selects products without a parent.'],
                    'limit' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'default' => 0, 'minimum' => 0],
                    'sortBy' => [
                        'type' => 'string',
                        'enum' => ['id', 'productNo', 'title', 'active', 'createdAt', 'updatedAt'],
                        'default' => 'id'
                    ],
                    'sortDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'DESC'],
                    'includePrices' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Include calculated prices. Requires product.view.prices.'
                    ]
                ]
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function search(
        ?string $query,
        ?string $lang,
        ?bool $active,
        ?int $categoryId,
        ?string $productType,
        ?int $parentId,
        ?int $limit,
        ?int $offset,
        ?string $sortBy,
        ?string $sortDirection,
        bool $includePrices
    ): array {
        $Connection = QUI::getDataBaseConnection();
        $limit = self::sanitizeLimit($limit);
        $offset = self::sanitizeOffset($offset);
        $lang = $lang ?: Products::getLocale()->getCurrent();
        $sortFields = [
            'id' => 'c.id',
            'productNo' => 'c.productNo',
            'title' => 'c.title',
            'active' => 'c.active',
            'createdAt' => 'c.c_date',
            'updatedAt' => 'c.e_date'
        ];
        $sortBy = $sortFields[$sortBy ?? 'id'] ?? $sortFields['id'];
        $sortDirection = $sortDirection === 'ASC' ? 'ASC' : 'DESC';

        $QueryBuilder = $Connection->createQueryBuilder();
        self::applyQuery(
            $QueryBuilder,
            $query,
            $lang,
            $active,
            $categoryId,
            $productType,
            $parentId
        );

        $rows = $QueryBuilder
            ->select('c.id')
            ->orderBy($sortBy, $sortDirection)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        $CountQuery = $Connection->createQueryBuilder();
        self::applyQuery(
            $CountQuery,
            $query,
            $lang,
            $active,
            $categoryId,
            $productType,
            $parentId
        );
        $count = (int)$CountQuery
            ->select('COUNT(c.id)')
            ->executeQuery()
            ->fetchOne();
        $products = [];

        foreach ($rows as $row) {
            try {
                $products[] = self::parseProduct(
                    self::getProduct((int)$row['id']),
                    $lang,
                    false,
                    $includePrices
                );
            } catch (Throwable $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return [
            'count' => $count,
            'limit' => $limit,
            'offset' => $offset,
            'products' => $products
        ];
    }

    protected static function applyQuery(
        QueryBuilder $QueryBuilder,
        ?string $query,
        string $lang,
        ?bool $active,
        ?int $categoryId,
        ?string $productType,
        ?int $parentId
    ): void {
        $Platform = QUI::getDataBaseConnection()->getDatabasePlatform();
        $QueryBuilder
            ->from($Platform->quoteSingleIdentifier(Tables::getProductCacheTableName()), 'c')
            ->innerJoin(
                'c',
                $Platform->quoteSingleIdentifier(Tables::getProductTableName()),
                'p',
                'p.id = c.id'
            )
            ->where('c.lang = :lang')
            ->setParameter('lang', $lang);

        if ($query !== null && $query !== '') {
            $conditions = [
                'c.productNo LIKE :query',
                'c.title LIKE :query',
                'c.description LIKE :query'
            ];

            if (ctype_digit($query)) {
                $conditions[] = 'c.id = :productId';
                $QueryBuilder->setParameter('productId', (int)$query);
            }

            $QueryBuilder
                ->andWhere($QueryBuilder->expr()->or(...$conditions))
                ->setParameter('query', '%' . $query . '%');
        }

        if ($active !== null) {
            $QueryBuilder->andWhere('c.active = :active')->setParameter('active', (int)$active);
        }

        if ($categoryId !== null) {
            $QueryBuilder
                ->andWhere('p.categories LIKE :category')
                ->setParameter('category', '%,' . $categoryId . ',%');
        }

        if ($productType !== null && $productType !== '') {
            $QueryBuilder->andWhere('c.type = :productType')->setParameter('productType', $productType);
        }

        if ($parentId !== null) {
            if ($parentId === 0) {
                $QueryBuilder->andWhere('c.parentId IS NULL');
            } else {
                $QueryBuilder->andWhere('c.parentId = :parentId')->setParameter('parentId', $parentId);
            }
        }
    }
}
