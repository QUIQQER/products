<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\BoolType;
use QUI\ERP\Products\Field\Types\BoolTypeFrontendView;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

class BoolTypeTest extends TestCase
{
    #[DataProvider('acceptedValues')]
    public function testSetValueNormalizesAcceptedBooleanRepresentations(mixed $value, int $expected): void
    {
        $Field = new BoolType(991033, ['name' => 'available']);

        $Field->setValue($value);

        self::assertSame($expected, $Field->getValue());
    }

    public static function acceptedValues(): iterable
    {
        yield 'boolean true' => [true, 1];
        yield 'boolean false' => [false, 0];
        yield 'positive integer' => [5, 1];
        yield 'negative integer' => [-2, 1];
        yield 'numeric zero' => ['0', 0];
        yield 'numeric decimal' => ['0.5', 1];
        yield 'uppercase true' => ['TRUE', 1];
        yield 'lowercase true' => ['true', 1];
        yield 'uppercase false' => ['FALSE', 0];
        yield 'lowercase false' => ['false', 0];
        yield 'empty value' => ['', 0];
    }

    #[DataProvider('invalidValues')]
    public function testSetValueRejectsUnsupportedRepresentations(mixed $value): void
    {
        $Field = new BoolType(991033, ['name' => 'available']);

        try {
            $Field->setValue($value);
            self::fail('Unsupported boolean representations must be rejected.');
        } catch (Exception $Exception) {
                self::assertSame(
                    \QUI::getLocale()->get('quiqqer/products', 'exception.field.invalid', [
                        'fieldId' => $Field->getId(),
                        'fieldTitle' => $Field->getTitle(),
                        'fieldType' => $Field->getType()
                    ]),
                    $Exception->getMessage()
                );
        }

        self::assertSame(0, $Field->getValue());
    }

    public static function invalidValues(): iterable
    {
        yield 'arbitrary text' => ['yes'];
        yield 'wrong letter case' => ['True'];
        yield 'surrounding whitespace' => [' true '];
        yield 'array' => [[]];
        yield 'null' => [null];
    }

    public function testViewsRenderTheNormalizedBooleanState(): void
    {
        $Field = new BoolType(991033, [
            'name' => 'available',
            'public' => true
        ]);

        $Field->setValue(true);

        $Backend = $Field->getBackendView();
        $Frontend = $Field->getFrontendView();

        self::assertInstanceOf(View::class, $Backend);
        self::assertSame(1, $Backend->getValue());
        self::assertInstanceOf(BoolTypeFrontendView::class, $Frontend);
        self::assertStringContainsString('fa-check', $Frontend->create());
        self::assertStringNotContainsString('fa-remove', $Frontend->create());

        $Field->setValue(false);
        $falseView = $Field->getFrontendView()->create();

        self::assertStringContainsString('fa-remove', $falseView);
        self::assertStringNotContainsString('fa-check', $falseView);
    }

    public function testMetadataDescribesBooleanStorageAndSearchBehavior(): void
    {
        $Field = new BoolType(991033, ['name' => 'available']);

        self::assertFalse($Field->isEmpty());
        self::assertSame('TINYINT(1)', $Field->getColumnType());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/BoolType',
            $Field->getJavaScriptControl()
        );
        self::assertSame([
            Search::SEARCHTYPE_BOOL,
            Search::SEARCHTYPE_CHECKBOX
        ], $Field->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_BOOL, $Field->getDefaultSearchType());
    }
}
