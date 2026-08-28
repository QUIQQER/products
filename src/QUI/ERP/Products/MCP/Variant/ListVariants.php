<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Variant\ListVariants
 */

namespace QUI\ERP\Products\MCP\Variant;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Utils\Tables;
use Throwable;

class ListVariants extends AbstractVariantTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int $productId,
                string | null $query = null,
                bool | null $active = null,
                string | null $lang = null,
                int | null $limit = null,
                int | null $offset = null,
                string | null $sortDirection = null,
                bool | null $includeFields = null,
                bool | null $includePrices = null
            ): CallToolResult | array {
                try {
                    self::checkProductsMcpPermission();

                    if ($includePrices === true) {
                        self::checkPermission('product.view.prices');
                    }

                    $Parent = self::getVariantParent($productId, true);
                    $Connection = QUI::getDataBaseConnection();
                    $lang = $lang ?: Products::getLocale()->getCurrent();
                    $limit = self::sanitizeLimit($limit);
                    $offset = self::sanitizeOffset($offset);
                    $direction = $sortDirection === 'DESC' ? 'DESC' : 'ASC';
                    $QueryBuilder = $Connection->createQueryBuilder();
                    self::applyQuery($QueryBuilder, $Parent->getId(), $lang, $query, $active);
                    $rows = $QueryBuilder
                        ->select('c.id')
                        ->orderBy('c.id', $direction)
                        ->setFirstResult($offset)
                        ->setMaxResults($limit)
                        ->executeQuery()
                        ->fetchAllAssociative();
                    $CountQuery = $Connection->createQueryBuilder();
                    self::applyQuery($CountQuery, $Parent->getId(), $lang, $query, $active);
                    $count = (int)$CountQuery->select('COUNT(c.id)')->executeQuery()->fetchOne();
                    $variants = [];

                    foreach ($rows as $row) {
                        try {
                            $Variant = self::getProduct((int)$row['id']);

                            if (!$Variant instanceof VariantChild) {
                                continue;
                            }

                            $variants[] = self::parseVariant(
                                $Variant,
                                $Parent,
                                $lang,
                                $includeFields === true,
                                $includePrices === true
                            );
                        } catch (Throwable $Exception) {
                            QUI\System\Log::writeDebugException($Exception);
                        }
                    }

                    return [
                        'variantParentId' => $Parent->getId(),
                        'defaultVariantId' => $Parent->getDefaultVariantId() ?: null,
                        'count' => $count,
                        'limit' => $limit,
                        'offset' => $offset,
                        'variants' => $variants
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_products_variants_list',
            description: 'Lists the variants belonging to a variant-parent product.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Variant parent or child ID.'],
                    'query' => ['type' => 'string', 'description' => 'Search by ID, product number or title.'],
                    'active' => ['type' => 'boolean'],
                    'lang' => ['type' => 'string'],
                    'limit' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'default' => 0, 'minimum' => 0],
                    'sortDirection' => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'ASC'],
                    'includeFields' => ['type' => 'boolean', 'default' => false],
                    'includePrices' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Include calculated prices. Requires product.view.prices.'
                    ]
                ]
            ]
        );
    }

    protected static function applyQuery(
        \Doctrine\DBAL\Query\QueryBuilder $QueryBuilder,
        int $parentId,
        string $lang,
        ?string $query,
        ?bool $active
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
            ->where('p.parent = :parentId')
            ->andWhere('c.lang = :lang')
            ->setParameter('parentId', $parentId)
            ->setParameter('lang', $lang);

        if ($query !== null && trim($query) !== '') {
            $conditions = ['c.productNo LIKE :query', 'c.title LIKE :query'];

            if (ctype_digit($query)) {
                $conditions[] = 'c.id = :variantId';
                $QueryBuilder->setParameter('variantId', (int)$query);
            }

            $QueryBuilder
                ->andWhere($QueryBuilder->expr()->or(...$conditions))
                ->setParameter('query', '%' . trim($query) . '%');
        }

        if ($active !== null) {
            $QueryBuilder->andWhere('c.active = :active')->setParameter('active', (int)$active);
        }
    }
}
