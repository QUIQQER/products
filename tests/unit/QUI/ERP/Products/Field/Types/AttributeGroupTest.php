<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\AttributeGroup;
use QUI\ERP\Products\Field\Types\AttributeGroupFrontendValueView;
use QUI\ERP\Products\Field\Types\AttributeGroupFrontendView;
use QUI\ERP\Products\Handler\Search;

class AttributeGroupTest extends TestCase
{
    public function testDefaultSelectionAndEntryStateManagement(): void
    {
        $Field = $this->createField();

        self::assertSame('blue', $Field->getValue());
        self::assertSame('Blau', $Field->getValueTitle());
        self::assertFalse($Field->isEmpty());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/AttributeGroup',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings',
            $Field->getJavaScriptSettings()
        );

        $Field->addEntry([]);
        $Field->addEntry(['valueId' => 'missing-title']);
        self::assertCount(2, $Field->getOption('entries'));

        $Field->addEntry([
            'title' => ['de' => 'Grün'],
            'valueId' => 'green',
            'image' => '/colors/green.png',
            'ignored' => true
        ]);
        $entries = $Field->getOption('entries');
        self::assertCount(3, $entries);
        self::assertSame('/colors/green.png', $entries[2]['image']);
        self::assertArrayNotHasKey('ignored', $entries[2]);

        $Field->disableEntries();
        $Field->hideEntries();
        self::assertTrue($Field->getOption('entries')[0]['disabled']);
        self::assertTrue($Field->getOption('entries')[2]['hide']);
        $Field->enableEntry(2);
        $Field->showEntry(2);
        self::assertFalse($Field->getOption('entries')[2]['disabled']);
        self::assertFalse($Field->getOption('entries')[2]['hide']);
        $Field->disableEntry(2);
        self::assertTrue($Field->getOption('entries')[2]['disabled']);

        $Field->clearValue();
        self::assertSame('blue', $Field->getValue());
        self::assertFalse($Field->getOption('entries')[1]['selected']);
    }

    public function testViewsExposeTheSelectedAttribute(): void
    {
        $Field = $this->createField();

        self::assertInstanceOf(AttributeGroupFrontendView::class, $Field->getFrontendView());
        self::assertInstanceOf(AttributeGroupFrontendValueView::class, $Field->getValueView());
        self::assertSame('blue', $Field->getFrontendView()->getValue());
        self::assertSame('blue', $Field->getValueView()->getValue());
    }

    #[DataProvider('cleanupValues')]
    public function testCleanupNormalizesSupportedRepresentations(mixed $input, mixed $expected): void
    {
        self::assertSame($expected, $this->createField()->cleanup($input));
    }

    public static function cleanupValues(): iterable
    {
        yield 'attribute identifier' => ['red', 'red'];
        yield 'numeric list index' => ['1', 'blue'];
        yield 'json custom value' => ['[2,"custom"]', '[2,"custom"]'];
        yield 'array custom value' => [[2, 'custom'], [2, 'custom']];
        yield 'incomplete json' => ['[2]', null];
        yield 'invalid json' => ['unknown', null];
        yield 'invalid custom key' => ['["red","custom"]', null];
        yield 'null' => [null, null];
        yield 'false' => [false, null];
        yield 'numeric integer' => [2, 2];
        yield 'invalid object' => [new \stdClass(), null];
    }

    public function testValidationAndSearchConfiguration(): void
    {
        $Field = $this->createField();
        $Field->validate('red');
        $Field->validate('blue');

        self::assertSame([
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_CHECKBOX_LIST
        ], $Field->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_SELECTMULTI, $Field->getDefaultSearchType());

        $this->expectException(Exception::class);
        $Field->validate('green');
    }

    private function createField(): AttributeGroup
    {
        return new AttributeGroup(9203, [
            'public' => true,
            'required' => true,
            'options' => [
                'entries_type' => AttributeGroup::ENTRIES_TYPE_COLOR,
                'entries' => [
                    [
                        'title' => ['de' => 'Rot'],
                        'valueId' => 'red'
                    ],
                    [
                        'title' => ['de' => 'Blau'],
                        'valueId' => 'blue',
                        'selected' => true
                    ]
                ]
            ]
        ]);
    }
}
