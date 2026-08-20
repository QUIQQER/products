<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\UnitSelect;
use QUI\ERP\Products\Field\Types\UnitSelectFrontendView;
use QUI\Locale;

class UnitSelectTest extends TestCase
{
    public function testDefaultSelectionEntriesAndControlMetadata(): void
    {
        $Field = $this->createField();

        self::assertSame(['id' => 0, 'quantity' => 2], $Field->getDefaultValue());
        self::assertSame(['id' => 0, 'quantity' => 2], $Field->getValue());

        $before = $Field->getOptions()['entries'];
        $Field->addEntry();
        $Field->addEntry(['quantityInput' => true]);
        self::assertSame($before, $Field->getOptions()['entries']);

        $Field->addEntry([
            'title' => ['de' => 'Liter', 'en' => 'litre'],
            'quantityInput' => false,
            'defaultQuantity' => 5
        ]);
        $entries = $Field->getOptions()['entries'];
        self::assertCount(3, $entries);
        self::assertSame([
            'title' => ['de' => 'Liter', 'en' => 'litre'],
            'quantityInput' => false,
            'defaultQuantity' => 5
        ], $entries[2]);

        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/UnitSelect',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings',
            $Field->getJavaScriptSettings()
        );
        self::assertInstanceOf(UnitSelectFrontendView::class, $Field->getFrontendView());
    }

    public function testCleanupNormalizesKnownUnitsAndFallsBackForInvalidValues(): void
    {
        $Field = $this->createField();
        $default = ['id' => 0, 'quantity' => 2];

        self::assertSame(
            ['id' => 0, 'quantity' => 3.5],
            $Field->cleanup(['id' => 0, 'quantity' => '3.5'])
        );
        self::assertSame(
            ['id' => 1, 'quantity' => false],
            $Field->cleanup(json_encode(['id' => 1, 'quantity' => 99], JSON_THROW_ON_ERROR))
        );
        self::assertSame($default, $Field->cleanup(null));
        self::assertSame($default, $Field->cleanup(new \stdClass()));
        self::assertSame($default, $Field->cleanup('{invalid'));
        self::assertSame($default, $Field->cleanup(['id' => 0]));
        self::assertSame($default, $Field->cleanup(['id' => 999, 'quantity' => 1]));
        self::assertSame($default, $Field->cleanup(['id' => 0, 'quantity' => '']));
    }

    public function testValidationAcceptsCompleteRepresentationsAndRejectsMalformedInput(): void
    {
        $Field = $this->createField();

        $Field->validate(null);
        self::assertSame(['id' => 0, 'quantity' => 2], $Field->cleanup(null));

        $Field->validate(['id' => 0, 'quantity' => 4]);
        self::assertSame(['id' => 0, 'quantity' => 4.0], $Field->cleanup(['id' => 0, 'quantity' => 4]));

        $jsonValue = json_encode(['id' => 1, 'quantity' => null], JSON_THROW_ON_ERROR);
        $Field->validate($jsonValue);
        self::assertSame(['id' => 1, 'quantity' => false], $Field->cleanup($jsonValue));

        foreach ([42, '{invalid', ['id' => 0], ['quantity' => 1]] as $invalidValue) {
            try {
                $Field->validate($invalidValue);
                self::fail('Malformed unit values must be rejected.');
            } catch (Exception $Exception) {
                self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
            }
        }
    }

    public function testLocalizedTitlesResolveKnownUnitsAndRejectIncompleteValues(): void
    {
        $Field = $this->createField();
        $Locale = $this->createMock(Locale::class);
        $Locale->method('getCurrent')->willReturn('de');

        self::assertSame('Kilogramm', $Field->getTitleByValue(['id' => 0, 'quantity' => 2], $Locale));
        self::assertSame('Stück', $Field->getTitleByValue(['id' => 1, 'quantity' => false], $Locale));
        self::assertSame('-', $Field->getTitleByValue(null, $Locale));
        self::assertSame('-', $Field->getTitleByValue(['id' => 999, 'quantity' => 1], $Locale));
        self::assertSame('-', $Field->getTitleByValue(['id' => 0, 'quantity' => 1], $this->createMock(Locale::class)));
    }

    private function createField(): UnitSelect
    {
        return new UnitSelect(991030, [
            'name' => 'unit',
            'options' => [
                'entries' => [
                    [
                        'title' => ['de' => 'Kilogramm', 'en' => 'kilogram'],
                        'default' => true,
                        'quantityInput' => true,
                        'defaultQuantity' => 2
                    ],
                    [
                        'title' => ['de' => 'Stück', 'en' => 'piece'],
                        'default' => false,
                        'quantityInput' => false,
                        'defaultQuantity' => false
                    ]
                ]
            ]
        ]);
    }
}
