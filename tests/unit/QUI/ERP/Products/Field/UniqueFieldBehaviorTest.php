<?php

namespace QUITests\ERP\Products\Unit\Field;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Types\AttributeGroupFrontendView;
use QUI\ERP\Products\Field\Types\ProductAttributeListBackendView;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Interfaces\ProductInterface;

class UniqueFieldBehaviorTest extends TestCase
{
    public function testConstructorRetainsPublicAttributesAndMutableState(): void
    {
        $Field = new UniqueField(990100, [
            'name' => 'material',
            'type' => 'Input',
            'prefix' => 'from ',
            'suffix' => ' kg',
            'priority' => 7,
            'options' => [
                'entries' => [
                    ['selected' => false],
                    ['selected' => true]
                ]
            ],
            'isRequired' => true,
            'isStandard' => true,
            'isSystem' => true,
            'isPublic' => false,
            'custom' => true,
            'unassigned' => true,
            'value' => 'steel',
            'ownField' => true,
            'showInDetails' => false,
            'searchvalue' => 'search-steel',
            'changeable' => false
        ]);

        self::assertSame(990100, $Field->getId());
        self::assertSame('material', $Field->getName());
        self::assertSame('Input', $Field->getType());
        self::assertSame('steel', $Field->getValue());
        self::assertSame('steel', $Field->getValueByLocale());
        self::assertSame('search-steel', $Field->getSearchCacheValue());
        self::assertTrue($Field->hasDefaultEntry());
        self::assertTrue($Field->isRequired());
        self::assertTrue($Field->isStandard());
        self::assertTrue($Field->isSystem());
        self::assertFalse($Field->isPublic());
        self::assertTrue($Field->isCustomField());
        self::assertTrue($Field->isUnassigned());
        self::assertTrue($Field->isOwnField());
        self::assertFalse($Field->showInDetails());
        self::assertFalse($Field->isChangeable());

        $Field->setChangeableStatus(true);
        self::assertTrue($Field->isChangeable());

        $WithoutSelection = new UniqueField(990101, [
            'options' => ['entries' => [['selected' => false]]],
            'searchvalue' => null
        ]);
        self::assertFalse($WithoutSelection->hasDefaultEntry());
        self::assertSame('', $WithoutSelection->getName());
        self::assertTrue($WithoutSelection->isPublic());
        self::assertTrue($WithoutSelection->showInDetails());
    }

    public function testPriceUsesNumericValueAndFallsBackToZero(): void
    {
        $Numeric = new UniqueField(990102, ['value' => '12.75', 'searchvalue' => null]);
        $Text = new UniqueField(990103, ['value' => 'not-a-price', 'searchvalue' => null]);

        self::assertSame(12.75, $Numeric->getPrice()->value());
        self::assertSame(0.0, $Text->getPrice()->value());
    }

    public function testViewsUseSpecializedParentAndGenericImplementations(): void
    {
        $AttributeGroup = new UniqueField(990104, [
            'type' => 'AttributeGroup',
            'options' => ['entries' => []],
            'searchvalue' => null
        ]);
        self::assertInstanceOf(AttributeGroupFrontendView::class, $AttributeGroup->getFrontendView());

        $AttributeList = new UniqueField(990105, [
            'type' => 'ProductAttributeList',
            'options' => ['entries' => []],
            'searchvalue' => null
        ]);
        self::assertInstanceOf(ProductAttributeListBackendView::class, $AttributeList->getBackendView());

        $Input = new UniqueField(990106, [
            'type' => 'Input',
            'value' => 'visible',
            'searchvalue' => 'visible'
        ]);
        $Input->setProduct($this->createMock(ProductInterface::class));
        self::assertInstanceOf(View::class, $Input->getView());

        $Generic = new UniqueField(990107, [
            'type' => 'MissingFieldType',
            'searchvalue' => null
        ]);
        self::assertSame('', $Generic->getParentClass());
        self::assertSame(View::class, $Generic->getFrontendView()::class);
        self::assertSame(View::class, $Generic->getBackendView()::class);
    }

    public function testCustomCalculationValueTextsSupportStringLocaleAndFallback(): void
    {
        self::assertSame('fixed label', $this->attributesFor([
            'custom_calc' => ['valueText' => 'fixed label']
        ])['valueText']);

        self::assertSame('Deutsch', $this->attributesFor([
            'custom_calc' => ['valueText' => ['de' => 'Deutsch', 'en' => 'English']]
        ])['valueText']);

        self::assertSame('English', $this->attributesFor([
            'custom_calc' => ['valueText' => ['en' => 'English']]
        ])['valueText']);

        self::assertSame('', $this->attributesFor([
            'custom_calc' => ['valueText' => 123]
        ])['valueText']);

        self::assertSame('engraving', $this->attributesFor([
            'custom' => true,
            'userInput' => 'engraving'
        ])['valueText']);

        self::assertSame('custom value', $this->attributesFor([
            'custom' => true,
            'value' => 'custom value'
        ])['valueText']);
    }

    public function testProductAttributeValueTextUsesSelectedDefaultAndStoredValue(): void
    {
        self::assertSame('Selected', $this->attributesFor([
            'type' => 'ProductAttributeList',
            'options' => ['entries' => [[
                'selected' => true,
                'title' => ['de' => 'Selected']
            ]]]
        ])['valueText']);

        self::assertSame('Default fallback', $this->attributesFor([
            'type' => 'ProductAttributeList',
            'options' => ['entries' => [[
                'default' => true,
                'title' => ['en' => 'Default fallback']
            ]]]
        ])['valueText']);

        self::assertSame('Stored numeric value', $this->attributesFor([
            'type' => 'ProductAttributeList',
            'value' => '7',
            'options' => ['entries' => [[
                'valueId' => 7,
                'title' => ['de' => 'Stored numeric value']
            ]]]
        ])['valueText']);
    }

    public function testAttributeGroupValueTextUsesValueSelectionAndFallback(): void
    {
        self::assertSame('Blue', $this->attributesFor([
            'type' => 'AttributeGroup',
            'value' => 'blue',
            'options' => ['entries' => [[
                'valueId' => 'blue',
                'title' => ['de' => 'Blue']
            ]]]
        ])['valueText']);

        self::assertSame('Fallback language', $this->attributesFor([
            'type' => 'AttributeGroup',
            'value' => 'blue',
            'options' => ['entries' => [[
                'valueId' => 'blue',
                'title' => ['en' => 'Fallback language']
            ]]]
        ])['valueText']);

        self::assertSame('Selected default', $this->attributesFor([
            'type' => 'AttributeGroup',
            'value' => 'missing',
            'options' => ['entries' => [[
                'valueId' => 'default',
                'selected' => true,
                'title' => ['de' => 'Selected default']
            ]]]
        ])['valueText']);

        self::assertSame('-', $this->attributesFor([
            'type' => 'AttributeGroup',
            'options' => ['entries' => []]
        ])['valueText']);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function attributesFor(array $params): array
    {
        return (new UniqueField(990108, array_merge([
            'name' => 'test-field',
            'type' => 'Input',
            'options' => [],
            'value' => null,
            'searchvalue' => null
        ], $params)))->getAttributes();
    }
}
