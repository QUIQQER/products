<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUITests\ERP\Products\Fixtures\TestUser;

class VariantDefaultSelectionTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    /**
     * @param list<int> $inactive
     */
    #[DataProvider('defaultSelections')]
    public function testFrontendAndPriceUseTheFirstActiveVariantWhenTheDefaultIsUnavailable(
        ?int $defaultIndex,
        array $inactive,
        bool $deleteDefault,
        ?int $expectedIndex,
        bool $ignoreDefault = false
    ): void {
        $Parent = ProductTestHelper::createProduct(
            ProductTestHelper::PREFIX . 'default-selection',
            25,
            VariantParent::class
        );
        $Attribute = ProductTestHelper::runAsSystemUser(static fn () => Fields::createField([
            'id' => 1804,
            'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
            'name' => 'PHPUnit default variant color',
            'publicField' => 1,
            'options' => [
                'exclude_from_variant_generation' => false,
                'entries' => [
                    ['valueId' => 'red', 'title' => ['de' => 'Rot', 'en' => 'Red']],
                    ['valueId' => 'blue', 'title' => ['de' => 'Blau', 'en' => 'Blue']],
                    ['valueId' => 'green', 'title' => ['de' => 'Grün', 'en' => 'Green']]
                ]
            ]
        ]));
        $Attribute->setOwnFieldStatus(true);
        $Parent->addField($Attribute);
        ProductTestHelper::runAsSystemUser(static function ($User) use ($Parent, $Attribute): void {
            $Parent->save($User);
            $Parent->generateVariants([[
                'fieldId' => $Attribute->getId(),
                'values' => ['red', 'blue', 'green']
            ]]);
        });
        $variants = $Parent->getVariants(['order' => 'id ASC']);
        self::assertCount(3, $variants);
        $defaultId = $defaultIndex === null ? false : $variants[$defaultIndex]->getId();
        $expectedId = $expectedIndex === null ? $Parent->getId() : $variants[$expectedIndex]->getId();

        ProductTestHelper::runAsSystemUser(
            static function ($User) use ($Parent, $variants, $defaultId, $inactive, $deleteDefault): void {
                $Parent->activate($User);

                foreach ($variants as $index => $Variant) {
                    $Variant->getField(Fields::FIELD_PRICE)->setValue(($index + 1) * 10);
                    $Variant->save($User);
                    $Variant->activate($User);
                }

                if ($defaultId !== false) {
                    $Parent->setDefaultVariant($defaultId);
                }

                $Parent->save($User);

                foreach ($inactive as $index) {
                    $variants[$index]->deactivate($User);
                }

                if ($deleteDefault) {
                    Products::getProduct($defaultId)->delete();
                }
            }
        );

        // Recreate the frontend request, avoiding backend views and cached pre-deactivation instances.
        Products::cleanProductInstanceMemCache();
        $Parent = Products::getNewProductInstance($Parent->getId());
        $Parent->setAttribute('viewType', 'frontend');

        foreach ($Parent->getVariants(['order' => 'id ASC']) as $Variant) {
            $Variant->setAttribute('viewType', 'frontend');
        }

        self::assertFalse($Parent->hasField(Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES));
        $Control = new ProductControl([
            'Product' => $Parent,
            'Project' => ProductTestHelper::getProject(),
            'Site' => ProductTestHelper::getCategorySite(),
            'ignoreDefaultVariant' => $ignoreDefault
        ]);
        $body = $Control->getBody();

        self::assertNotSame('', $body);
        self::assertSame($expectedId, $Control->getAttribute('data-productid'));
        self::assertSame($expectedIndex !== null, $Control->getAttribute('data-qui-option-show-price'));
        self::assertSame($expectedIndex !== null, $Control->getAttribute('data-qui-option-available'));

        // Reading the frontend must not change the stored standard variant selection.
        self::assertSame($defaultId, Products::getNewProductInstance($Parent->getId())->getDefaultVariantId());

        if (!$ignoreDefault) {
            $expectedPrice = 25.0;

            if ($expectedIndex !== null) {
                $expectedPrice = ($expectedIndex + 1) * 10.0;
            } elseif ($defaultIndex === null) {
                $expectedPrice = 10.0;
            }

            self::assertSame($expectedPrice, $Parent->getCurrentPrice(new TestUser(TestUser::TYPE_NETTO))->value());
        }
    }

    public static function defaultSelections(): iterable
    {
        yield 'active configured default takes precedence' => [2, [], false, 2];
        yield 'inactive default falls back to the earliest active variant' => [2, [2], false, 0];
        yield 'inactive default skips inactive earlier variants' => [2, [0, 2], false, 1];
        yield 'deleted default falls back to the earliest active variant' => [2, [], true, 0];
        yield 'deleted default skips inactive earlier variants' => [2, [0], true, 1];
        yield 'inactive default without active variants' => [2, [0, 1, 2], false, null];
        yield 'deleted default without active variants' => [2, [0, 1], true, null];
        yield 'no default remains unselected' => [null, [], false, null];
        yield 'explicitly ignored default remains unselected' => [2, [2], false, null, true];
    }
}
