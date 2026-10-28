<?php

namespace QUITests\ERP\Products\Unit\Controls;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Money\Price;
use QUI\ERP\Products\Controls\Price as PriceControl;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\Product;
use QUI\ERP\Products\Product\ViewFrontend;
use QUI\ERP\Products\Utils\Package;
use QUI\Projects\Media\Image;
use ReflectionProperty;

class SuggestResultPriceTest extends TestCase
{
    #[DataProvider('suggestionProvider')]
    public function testSuggestionPricesRespectVisibilityAndStartingPrefix(
        string $template,
        bool $showPrice,
        bool $hidden,
        bool $starting
    ): void {
        $Price = new Price(19.9, QUI\ERP\Defaults::getCurrency());

        if ($starting) {
            $Price->enableMinimalPrice();
        }

        $PriceControl = new PriceControl(['Price' => $Price, 'withVatText' => false]);
        $View = $this->createMock(ViewFrontend::class);
        $View->method('getPriceDisplay')->willReturn($PriceControl);
        $Image = $this->createMock(Image::class);
        $Image->method('getSizeCacheUrl')->willReturn('/phpunit-product.png');
        $Product = $this->createMock(Product::class);
        $Product->method('getUrl')->willReturn('/phpunit-product');
        $Product->method('getImage')->willReturn($Image);
        $Product->method('getTitle')->willReturn('PHPUnit product');
        $Product->method('getDescription')->willReturn('PHPUnit description');
        $Product->method('getFieldValueByLocale')->willReturn('PHPUNIT-389');
        $Product->expects($showPrice && !$hidden ? self::once() : self::never())
            ->method('getViewFrontend')->willReturn($View);

        $Products = new ReflectionProperty(Products::class, 'list');
        $previousProducts = $Products->getValue();
        $Visibility = new ReflectionProperty(Package::class, 'hidePrice');
        $previousVisibility = $Visibility->getValue();
        $previousSessionVisibility = QUI::getSession()->get('QUIQQER_PRODUCTS_HIDE_PRICE');
        $Products->setValue(null, [389 => $Product]);
        $Visibility->setValue(null, $hidden);
        QUI::getSession()->set('QUIQQER_PRODUCTS_HIDE_PRICE', (int)$hidden);

        try {
            $Engine = QUI::getTemplateManager()->getEngine();
            $Engine->assign([
                'result' => [389],
                'Locale' => QUI::getLocale(),
                'pages' => 1,
                'active' => 1,
                'showLinkToSearchSite' => false,
                'searchUrl' => '',
                'showDesc' => true,
                'showPrice' => $showPrice,
                'hidePrice' => Package::hidePrice()
            ]);
            $html = $Engine->fetch(OPT_DIR . $template);
        } finally {
            $Products->setValue(null, $previousProducts);
            $Visibility->setValue(null, $previousVisibility);
            QUI::getSession()->set('QUIQQER_PRODUCTS_HIDE_PRICE', $previousSessionVisibility);
        }

        $Document = new DOMDocument();
        self::assertTrue($Document->loadHTML('<?xml encoding="UTF-8">' . $html));
        $XPath = new DOMXPath($Document);
        $visible = $showPrice && !$hidden;
        self::assertSame($visible ? 1.0 : 0.0, $XPath->evaluate('count(//*[@data-name="suggestPrice"])'));
        self::assertSame(
            $visible && $starting ? 1.0 : 0.0,
            $XPath->evaluate('count(//span[@class="qui-products-price-display-prefix"])')
        );

        if ($visible) {
            self::assertStringContainsString('19,90', $html);
        }
    }

    /** @return array<string, array{string, bool, bool, bool}> */
    public static function suggestionProvider(): array
    {
        $cases = [];

        $templates = [
            'desktop' => 'quiqqer/products/template/search/frontend/SuggestRendered.html',
            'mobile' => 'quiqqer/productsearch/template/SuggestRendered.html'
        ];

        foreach ($templates as $name => $template) {
            $cases[$name . ' fixed price'] = [$template, true, false, false];
            $cases[$name . ' starting price'] = [$template, true, false, true];
            $cases[$name . ' disabled'] = [$template, false, false, true];
            $cases[$name . ' globally hidden'] = [$template, true, true, true];
        }

        return $cases;
    }
}
