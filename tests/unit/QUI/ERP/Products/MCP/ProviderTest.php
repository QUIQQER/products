<?php

namespace QUITests\ERP\Products\MCP;

use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\MCP\Provider;

class ProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Server::setRequestUser(null);
        parent::tearDown();
    }

    public function testProviderRegistersProductManagementToolsForSystemUser(): void
    {
        Server::setRequestUser(QUI::getUsers()->getSystemUser());
        $Builder = new Builder();

        (new Provider())->register($Builder);
        $tools = $Builder->getTools();

        self::assertSame([
            'quiqqer_products_search',
            'quiqqer_products_get',
            'quiqqer_products_create',
            'quiqqer_products_copy',
            'quiqqer_products_update',
            'quiqqer_products_activate',
            'quiqqer_products_deactivate',
            'quiqqer_products_delete',
            'quiqqer_products_permissions_get',
            'quiqqer_products_permissions_update'
        ], array_keys($tools));

        foreach ($tools as $tool) {
            self::assertIsCallable($tool['callback']);
            self::assertNotSame('', $tool['description']);
            self::assertSame('object', $tool['inputSchema']['type']);
            self::assertFalse($tool['inputSchema']['additionalProperties']);
        }
    }

    public function testProviderRegistersNoToolsForNobody(): void
    {
        Server::setRequestUser(new QUI\Users\Nobody());
        $Builder = new Builder();

        (new Provider())->register($Builder);

        self::assertSame([], $Builder->getTools());
    }
}
