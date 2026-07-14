<?php

namespace QUI\ERP\Products\Utils;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use QUI;

use function array_map;
use function array_values;
use function explode;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * DBAL query helpers for the package's public array-based search parameters.
 */
final class Database
{
    /**
     * @param mixed $conditions
     * @param string[] $allowedFields
     */
    public static function areConditionsValid(mixed $conditions, array $allowedFields): bool
    {
        if (!is_array($conditions)) {
            return false;
        }

        foreach ($conditions as $field => $condition) {
            if (!is_string($field) || !in_array($field, $allowedFields, true)) {
                return false;
            }

            if (is_array($condition) && (!isset($condition['type']) || !array_key_exists('value', $condition))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $allowedFields
     */
    public static function isOrderValid(mixed $order, array $allowedFields): bool
    {
        if (!is_string($order)) {
            return false;
        }

        $parts = preg_split('/\s+/', trim($order), 2);
        $field = (string)($parts[0] ?? '');
        $direction = strtoupper((string)($parts[1] ?? 'ASC'));

        return in_array($field, $allowedFields, true) && in_array($direction, ['ASC', 'DESC'], true);
    }

    public static function dropColumn(string $tableName, string $columnName): void
    {
        $SchemaManager = QUI::getSchemaManager();
        $Table = $SchemaManager->introspectTable($tableName);

        if (!$Table->hasColumn($columnName)) {
            return;
        }

        $SchemaManager->alterTable(new TableDiff(
            $Table,
            droppedColumns: [$columnName => $Table->getColumn($columnName)]
        ));
    }

    public static function addColumn(string $tableName, string $columnName, string $columnType): void
    {
        $SchemaManager = QUI::getSchemaManager();
        $Table = $SchemaManager->introspectTable($tableName);

        if ($Table->hasColumn($columnName)) {
            return;
        }

        $SchemaManager->alterTable(new TableDiff(
            $Table,
            addedColumns: [self::createColumn($columnName, $columnType)]
        ));
    }

    public static function changeColumn(string $tableName, string $columnName, string $columnType): void
    {
        $SchemaManager = QUI::getSchemaManager();
        $Table = $SchemaManager->introspectTable($tableName);
        $Column = self::createColumn($columnName, $columnType);

        if (!$Table->hasColumn($columnName)) {
            $SchemaManager->alterTable(new TableDiff($Table, addedColumns: [$Column]));
            return;
        }

        $SchemaManager->alterTable(new TableDiff(
            $Table,
            changedColumns: [$columnName => new ColumnDiff($Table->getColumn($columnName), $Column)]
        ));
    }

    /**
     * @return array<string, Column>
     */
    public static function getColumns(string $tableName): array
    {
        $columns = [];

        foreach (QUI::getSchemaManager()->introspectTable($tableName)->getColumns() as $Column) {
            $columns[$Column->getName()] = $Column;
        }

        return $columns;
    }

    private static function createColumn(string $columnName, string $columnType): Column
    {
        $normalizedType = strtoupper(trim($columnType));
        $dbalType = self::normalizeColumnType($columnType);

        $options = ['notnull' => false];

        if ($dbalType === 'string' && preg_match('/VARCHAR\((\d+)\)/', $normalizedType, $matches)) {
            $options['length'] = (int)$matches[1];
        }

        return new Column($columnName, Type::getType($dbalType), $options);
    }

    public static function normalizeColumnType(string $columnType): string
    {
        $normalizedType = strtoupper(trim($columnType));

        return match (true) {
            str_starts_with($normalizedType, 'BIGINT') => 'bigint',
            str_starts_with($normalizedType, 'SMALLINT') => 'smallint',
            str_starts_with($normalizedType, 'TINYINT') => 'boolean',
            str_starts_with($normalizedType, 'INT') => 'integer',
            str_starts_with($normalizedType, 'DOUBLE'),
            str_starts_with($normalizedType, 'FLOAT'),
            str_starts_with($normalizedType, 'DECIMAL') => 'float',
            str_starts_with($normalizedType, 'VARCHAR') => 'string',
            default => 'text'
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function fetch(array $params): array
    {
        $QueryBuilder = QUI::getQueryBuilder();
        $table = $params['from'] ?? null;

        if (!is_string($table) || $table === '') {
            throw new QUI\Exception('A database table is required.');
        }

        if (isset($params['count'])) {
            $count = is_array($params['count']) ? $params['count'] : [];
            $field = self::quoteField((string)($count['select'] ?? '*'));
            $alias = self::quoteField((string)($count['as'] ?? 'count'));
            $QueryBuilder->select("COUNT($field) AS $alias");
        } else {
            $QueryBuilder->select(...self::normalizeSelect($params['select'] ?? '*'));
        }

        $QueryBuilder->from(QUI\Utils\Doctrine::quoteIdentifier($table));
        self::applyConditions($QueryBuilder, $params['where'] ?? [], false);
        self::applyConditions($QueryBuilder, $params['where_or'] ?? [], true);

        if (!empty($params['order'])) {
            self::applyOrder($QueryBuilder, (string)$params['order']);
        }

        QUI\Utils\Doctrine::applyLimit($QueryBuilder, $params['limit'] ?? null);

        return $QueryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array<int, string>
     */
    private static function normalizeSelect(mixed $select): array
    {
        if (is_string($select)) {
            $select = explode(',', $select);
        }

        if (!is_array($select)) {
            return ['*'];
        }

        return array_map(
            static fn(mixed $field): string => self::quoteField(trim((string)$field)),
            array_values($select)
        );
    }

    private static function quoteField(string $field): string
    {
        if ($field === '*') {
            return $field;
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
            throw new QUI\Exception(sprintf('Invalid database field "%s".', $field));
        }

        return QUI\Utils\Doctrine::quoteIdentifier($field);
    }

    /**
     * @param array<string, mixed>|mixed $conditions
     */
    private static function applyConditions(QueryBuilder $QueryBuilder, mixed $conditions, bool $useOr): void
    {
        if (!is_array($conditions) || $conditions === []) {
            return;
        }

        $expressions = [];

        foreach ($conditions as $field => $condition) {
            $column = self::quoteField((string)$field);
            $operator = '=';
            $value = $condition;

            if (is_array($condition) && isset($condition['type'])) {
                $operator = strtoupper((string)$condition['type']);
                $value = $condition['value'] ?? null;
            }

            $parameterBase = 'productQuery' . count($QueryBuilder->getParameters());

            if ($operator === 'IN' || $operator === 'NOT IN') {
                $values = is_array($value) ? array_values($value) : [$value];

                if ($values === []) {
                    $expressions[] = $operator === 'IN' ? '1 = 0' : '1 = 1';
                    continue;
                }

                $placeholders = [];

                foreach ($values as $index => $entry) {
                    $parameter = $parameterBase . '_' . $index;
                    $placeholders[] = ':' . $parameter;
                    $QueryBuilder->setParameter($parameter, $entry);
                }

                $expressions[] = sprintf('%s %s (%s)', $column, $operator, implode(', ', $placeholders));
                continue;
            }

            if ($value === null) {
                $expressions[] = $operator === 'NOT' || $operator === '!=' || $operator === '<>'
                    ? $QueryBuilder->expr()->isNotNull($column)
                    : $QueryBuilder->expr()->isNull($column);
                continue;
            }

            $parameter = $parameterBase;

            if ($operator === '%LIKE%') {
                $operator = 'LIKE';
                $value = '%' . $value . '%';
            } elseif ($operator === 'LIKE%') {
                $operator = 'LIKE';
                $value .= '%';
            } elseif ($operator === '%LIKE') {
                $operator = 'LIKE';
                $value = '%' . $value;
            } elseif ($operator === 'NOT') {
                $operator = '<>';
            }

            if (!in_array($operator, ['=', '<>', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'], true)) {
                throw new QUI\Exception(sprintf('Unsupported database operator "%s".', $operator));
            }

            $expressions[] = sprintf('%s %s :%s', $column, $operator, $parameter);
            $QueryBuilder->setParameter($parameter, $value);
        }

        $expression = $useOr
            ? $QueryBuilder->expr()->or(...$expressions)
            : $QueryBuilder->expr()->and(...$expressions);

        $QueryBuilder->andWhere($expression);
    }

    private static function applyOrder(QueryBuilder $QueryBuilder, string $order): void
    {
        $parts = preg_split('/\s+/', trim($order), 2);
        $field = (string)($parts[0] ?? '');
        $direction = strtoupper((string)($parts[1] ?? 'ASC'));

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new QUI\Exception(sprintf('Invalid database sort direction "%s".', $direction));
        }

        $QueryBuilder->orderBy(self::quoteField($field), $direction);
    }
}
