<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Product\UniqueProduct;
use QUI\Locale;
use QUITests\ERP\Products\Fixtures\TestUser;
use RuntimeException;

class ProductListLocaleTest extends TestCase
{
    private Locale $OriginalLocale;
    private Locale $English;

    protected function setUp(): void
    {
        $this->OriginalLocale = Products::getLocale();
        $this->English = new Locale();
        $this->English->setCurrent('en');
        Products::setLocale($this->English);
    }

    protected function tearDown(): void
    {
        Products::setLocale($this->OriginalLocale);
    }

    public static function exportLanguages(): iterable
    {
        yield 'customer language' => [null, 'de'];
        yield 'explicit language' => ['fr', 'fr'];
    }

    #[DataProvider('exportLanguages')]
    public function testSerializationUsesRequestedLanguageWithoutChangingSubsequentProducts(
        ?string $language,
        string $expectedLanguage
    ): void {
        $Locale = null;

        if ($language !== null) {
            $Locale = new Locale();
            $Locale->setCurrent($language);
        }

        $Product = $this->createMock(UniqueProduct::class);
        $Product->method('getAttributes')->willReturnCallback(static function () use ($expectedLanguage): array {
            self::assertSame($expectedLanguage, Products::getLocale()->getCurrent());
            return ['title' => 'exported in ' . Products::getLocale()->getCurrent()];
        });
        $List = new ProductList([], new TestUser());
        $List->addProduct($Product);
        $data = $List->toArray($Locale);

        self::assertSame('exported in ' . $expectedLanguage, $data['products'][0]['title']);
        self::assertSame($this->English, Products::getLocale());
        self::assertSame('en', Products::getLocale()->getCurrent());
        // Repeat the call to cover serialization of already calculated lists as well.
        self::assertSame($data, $List->toArray($Locale));
        self::assertSame($this->English, Products::getLocale());
    }

    public function testCalculationFailureRestoresThePreviousLocale(): void
    {
        $List = $this->getMockBuilder(ProductList::class)
            ->setConstructorArgs([[], new TestUser()])
            ->onlyMethods(['calc'])
            ->getMock();
        $List->method('calc')->willThrowException(new RuntimeException('Calculation failed'));

        try {
            $List->toArray();
            self::fail('The calculation failure must propagate.');
        } catch (RuntimeException $Exception) {
            self::assertSame('Calculation failed', $Exception->getMessage());
        }

        self::assertSame($this->English, Products::getLocale());
    }

    public function testProductExportFailureRestoresThePreviousLocale(): void
    {
        $Product = $this->createMock(UniqueProduct::class);
        $Product->method('getAttributes')->willThrowException(new RuntimeException('Product export failed'));
        $List = new ProductList([], new TestUser());
        $List->addProduct($Product);

        try {
            $List->toArray();
            self::fail('The product export failure must propagate.');
        } catch (RuntimeException $Exception) {
            self::assertSame('Product export failed', $Exception->getMessage());
        }

        self::assertSame($this->English, Products::getLocale());
    }

    public function testNestedSerializationRestoresEachCallersLocale(): void
    {
        $French = new Locale();
        $French->setCurrent('fr');
        $InnerList = new ProductList([], new TestUser());
        $Product = $this->createMock(UniqueProduct::class);
        $Product->method('getAttributes')->willReturnCallback(static function () use ($InnerList, $French): array {
            $CallerLocale = Products::getLocale();
            self::assertSame('de', $CallerLocale->getCurrent());
            $InnerList->toArray($French);
            self::assertSame($CallerLocale, Products::getLocale());
            return [];
        });
        $List = new ProductList([], new TestUser());
        $List->addProduct($Product);
        $List->toArray();

        self::assertSame($this->English, Products::getLocale());
    }
}
