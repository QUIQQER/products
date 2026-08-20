<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI\Ajax;
use QUI\Permissions\Permission;
use QUI\Users\User;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use ReflectionProperty;

abstract class AjaxTestCase extends ProductIntegrationTestCase
{
    /** @var array<string, mixed> */
    private array $ajaxState = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['callables', 'functions', 'permissions'] as $property) {
            $Property = new ReflectionProperty(Ajax::class, $property);
            $this->ajaxState[$property] = $Property->getValue();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->ajaxState as $property => $value) {
            (new ReflectionProperty(Ajax::class, $property))->setValue(null, $value);
        }

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
