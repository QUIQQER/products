<?php

namespace QUITests\ERP\Products\Integration\Field;

use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Handler\Fields;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class FieldLifecycleTest extends ProductIntegrationTestCase
{
    public function testSavedFieldIsReloadedWithFreshAttributes(): void
    {
        $Field = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1502,
            'name' => ProductTestHelper::PREFIX . 'field-save-cache',
            'type' => Fields::TYPE_INPUT,
            'publicField' => 1,
            'priority' => 2
        ]));

        try {
            $Field->setPublicStatus(false);
            $Field->setAttribute('priority', 42);
            ProductTestHelper::runAsSystemUser(static function () use ($Field): void {
                $Field->save();
            });

            $Reloaded = Fields::getField($Field->getId());
            self::assertFalse($Reloaded->isPublic());
            self::assertSame(42, (int)$Reloaded->getAttribute('priority'));
        } finally {
            ProductTestHelper::runAsSystemUser(static function () use ($Field): void {
                $Field->delete();
            });
        }
    }

    public function testDeletedFieldIsNoLongerReturnedFromRuntimeCache(): void
    {
        $Field = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1501,
            'name' => ProductTestHelper::PREFIX . 'field-delete-cache',
            'type' => Fields::TYPE_INPUT,
            'publicField' => 1
        ]));
        $fieldId = $Field->getId();

        ProductTestHelper::runAsSystemUser(static function () use ($Field): void {
            $Field->delete();
        });

        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);
        Fields::getField($fieldId);
    }
}
