<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\Ajax;
use QUI\Messages\Handler as MessageHandler;
use QUI\Messages\Message;
use QUI\Permissions\Permission;
use QUI\Users\User;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use ReflectionProperty;

abstract class AjaxTestCase extends ProductIntegrationTestCase
{
    /** @var array<string, mixed> */
    private array $ajaxState = [];

    /** @var array<string, Message> */
    private array $messageState = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['callables', 'functions', 'permissions'] as $property) {
            $Property = new ReflectionProperty(Ajax::class, $property);
            $this->ajaxState[$property] = $Property->getValue();
        }

        $Messages = new ReflectionProperty(MessageHandler::class, 'messages');
        $this->messageState = $Messages->getValue(QUI::getMessagesHandler());
        $Messages->setValue(QUI::getMessagesHandler(), []);
    }

    protected function tearDown(): void
    {
        foreach ($this->ajaxState as $property => $value) {
            (new ReflectionProperty(Ajax::class, $property))->setValue(null, $value);
        }

        (new ReflectionProperty(MessageHandler::class, 'messages'))->setValue(
            QUI::getMessagesHandler(),
            $this->messageState
        );

        parent::tearDown();
    }

    protected function invokeEndpoint(string $file, string $name, mixed ...$arguments): mixed
    {
        require dirname(__DIR__, 6) . '/ajax/' . $file;

        $callables = Ajax::getRegisteredCallables();
        self::assertArrayHasKey($name, $callables);

        return $callables[$name]['callable'](...$arguments);
    }

    protected function invokeEndpointAsAdmin(string $file, string $name, mixed ...$arguments): mixed
    {
        $PermissionUser = new ReflectionProperty(Permission::class, 'User');
        $originalUser = $PermissionUser->getValue();
        $Admin = $this->createMock(User::class);
        $Admin->method('isSU')->willReturn(true);
        $PermissionUser->setValue(null, $Admin);

        try {
            return $this->invokeEndpoint($file, $name, ...$arguments);
        } finally {
            $PermissionUser->setValue(null, $originalUser);
        }
    }
}
