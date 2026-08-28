<?php

namespace QUITests\ERP\Products\Integration;

use Mcp\Server\Builder;
use Mcp\Schema\Result\CallToolResult;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\MCP\Provider;
use QUI\ERP\Products\Utils\Tables;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class McpFieldToolsTest extends TestCase
{
    protected function tearDown(): void
    {
        Server::setRequestUser(null);
        parent::tearDown();
    }

    public function testFieldTypeAndLifecycleTools(): void
    {
        Server::setRequestUser(QUI::getUsers()->getSystemUser());
        ProductTestHelper::runAsSystemUser(static function (): void {
            $Builder = new Builder();
            (new Provider())->register($Builder);
            $tools = $Builder->getTools();
            $suffix = bin2hex(random_bytes(5));
            $fieldId = self::findUnusedCustomFieldId();
            $name = 'mcp-field-' . $suffix;

            try {
                $types = $tools['quiqqer_products_field_types_list']['callback']('de');
                self::assertGreaterThan(0, $types['count']);
                self::assertContains(Fields::TYPE_INPUT, array_column($types['fieldTypes'], 'name'));

                $created = self::requireSuccessfulResult($tools['quiqqer_products_fields_create']['callback'](
                    Fields::TYPE_INPUT,
                    ['de' => ['title' => 'MCP Feld ' . $suffix, 'description' => 'Beschreibung']],
                    $fieldId,
                    $name,
                    ['mcpFixture' => true],
                    true,
                    false,
                    true,
                    42,
                    ['de' => 'vor'],
                    ['de' => 'nach'],
                    'de'
                ));
                self::assertSame($fieldId, $created['id']);
                self::assertSame(Fields::TYPE_INPUT, $created['type']);
                self::assertSame($name, $created['name']);
                self::assertSame(42, $created['priority']);
                self::assertTrue($created['public']);
                self::assertTrue($created['showInDetails']);
                self::assertTrue($created['options']['mcpFixture']);

                $search = $tools['quiqqer_products_fields_search']['callback'](
                    $name,
                    Fields::TYPE_INPUT,
                    false,
                    false,
                    true,
                    true,
                    'de',
                    20,
                    0,
                    'id',
                    'ASC'
                );
                self::assertSame(1, $search['count']);
                self::assertSame($fieldId, $search['fields'][0]['id']);

                $updatedName = $name . '-updated';
                $updated = $tools['quiqqer_products_fields_update']['callback'](
                    $fieldId,
                    [
                        'name' => $updatedName,
                        'priority' => 84,
                        'required' => true,
                        'defaultValue' => 'Default MCP value',
                        'options' => ['updated' => true],
                        'prefixes' => ['de' => 'neu-vor'],
                        'suffixes' => ['de' => 'neu-nach']
                    ],
                    ['de' => ['title' => 'Aktualisiertes MCP Feld ' . $suffix]],
                    'de'
                );
                self::assertSame($updatedName, $updated['name']);
                self::assertSame(84, $updated['priority']);
                self::assertTrue($updated['required']);
                self::assertSame('Default MCP value', $updated['defaultValue']);
                self::assertTrue($updated['options']['updated']);
                self::assertSame('neu-vor', $updated['prefixes']['de']);
                self::assertSame('neu-nach', $updated['suffixes']['de']);

                $field = $tools['quiqqer_products_fields_get']['callback']($fieldId, 'de');
                self::assertSame($fieldId, $field['id']);
                self::assertArrayHasKey('translations', $field);
                self::assertArrayHasKey('productCount', $field);

                $deleted = $tools['quiqqer_products_fields_delete']['callback']($fieldId, false, 'de');
                self::assertTrue($deleted['deleted']);
                self::assertSame($fieldId, $deleted['field']['id']);
                self::assertFieldDeletedFromDatabase($fieldId);
            } finally {
                if (self::fieldExistsInDatabase($fieldId)) {
                    Fields::getField($fieldId)->deleteSystemField();
                }
            }
        });
    }

    private static function findUnusedCustomFieldId(): int
    {
        for ($fieldId = 8000; $fieldId < 9000; $fieldId++) {
            if (!self::fieldExistsInDatabase($fieldId)) {
                return $fieldId;
            }
        }

        self::fail('No free custom product-field ID is available for the MCP test.');
    }

    /**
     * @param array<string, mixed>|CallToolResult $result
     * @return array<string, mixed>
     */
    private static function requireSuccessfulResult(array | CallToolResult $result): array
    {
        if (is_array($result)) {
            return $result;
        }

        $messages = [];

        foreach ($result->content as $content) {
            if (is_string($content)) {
                $messages[] = $content;
                continue;
            }

            if (is_object($content) && property_exists($content, 'text')) {
                $messages[] = (string)$content->text;
            }
        }

        self::fail('Field MCP call failed: ' . implode('; ', $messages));
    }

    private static function fieldExistsInDatabase(int $fieldId): bool
    {
        $count = QUI::getDataBaseConnection()
            ->createQueryBuilder()
            ->select('COUNT(id)')
            ->from(Tables::getFieldTableName())
            ->where('id = :fieldId')
            ->setParameter('fieldId', $fieldId)
            ->executeQuery()
            ->fetchOne();

        return (int)$count > 0;
    }

    private static function assertFieldDeletedFromDatabase(int $fieldId): void
    {
        self::assertFalse(self::fieldExistsInDatabase($fieldId));
    }
}
