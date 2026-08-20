<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cache\LongTermCache;
use QUI\ERP\Products\Handler\Cache;
use QUI\ERP\Products\Product\Types\DigitalProduct;
use QUI\ERP\Products\Product\Types\Product;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\ProductTypes;

class ProductTypesTest extends TestCase
{
    /** @var array<string, array{exists: bool, value: mixed}> */
    private array $originalCacheEntries = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->getCacheNames() as $cacheName) {
            try {
                $this->originalCacheEntries[$cacheName] = [
                    'exists' => true,
                    'value' => LongTermCache::get($cacheName)
                ];
            } catch (QUI\Exception) {
                $this->originalCacheEntries[$cacheName] = [
                    'exists' => false,
                    'value' => null
                ];
            }

            LongTermCache::clear($cacheName);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalCacheEntries as $cacheName => $entry) {
            if ($entry['exists']) {
                LongTermCache::set($cacheName, $entry['value']);
            } else {
                LongTermCache::clear($cacheName);
            }
        }

        parent::tearDown();
    }

    public function testInstalledProductTypesAreDiscoveredAndCached(): void
    {
        $Types = ProductTypes::getInstance();
        $types = $Types->getProductTypes();

        self::assertContains('\\' . Product::class, $types);
        self::assertContains('\\' . VariantChild::class, $types);
        self::assertContains('\\' . VariantParent::class, $types);
        self::assertContains('\\' . DigitalProduct::class, $types);
        self::assertSame($types, $Types->getProductTypes());
    }

    public function testVariantTypesAreFilteredByTheirInheritance(): void
    {
        $Types = ProductTypes::getInstance();
        $parentTypes = $Types->getVariantParentProductTypes();
        $childTypes = $Types->getVariantChildProductTypes();

        self::assertContains('\\' . VariantParent::class, $parentTypes);
        self::assertNotContains('\\' . Product::class, $parentTypes);
        self::assertNotContains('\\' . VariantChild::class, $parentTypes);
        self::assertContains('\\' . VariantChild::class, $childTypes);
        self::assertNotContains('\\' . Product::class, $childTypes);
        self::assertNotContains('\\' . VariantParent::class, $childTypes);
        self::assertSame($parentTypes, $Types->getVariantParentProductTypes());
        self::assertSame($childTypes, $Types->getVariantChildProductTypes());
    }

    public function testExistsNormalizesLeadingAndTrailingNamespaceSeparators(): void
    {
        $Types = ProductTypes::getInstance();

        self::assertTrue($Types->exists(VariantParent::class));
        self::assertTrue($Types->exists('\\' . VariantParent::class . '\\'));
        self::assertFalse($Types->exists('QUI\\ERP\\Products\\Product\\Types\\MissingProductType'));
    }

    /**
     * @return string[]
     */
    private function getCacheNames(): array
    {
        $basePath = Cache::getBasicCachePath();

        return [
            $basePath . 'types',
            $basePath . 'types/variant_parents',
            $basePath . 'types/variant_child'
        ];
    }
}
