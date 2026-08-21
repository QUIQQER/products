<?php

namespace QUITests\ERP\Products\Unit\Field;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Types\CheckboxInput;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Field\View;

class FieldBehaviorTest extends TestCase
{
    public function testConstructorRetainsNameFlagsValuesAndOptions(): void
    {
        $Field = new Input(990109, [
            'name' => 'material',
            'public' => false,
            'system' => true,
            'required' => true,
            'standard' => false,
            'showInDetails' => true,
            'defaultValue' => 'fallback',
            'value' => 'steel',
            'options' => '{"trim":true}'
        ]);

        self::assertSame(990109, $Field->getId());
        self::assertSame('material', $Field->getName());
        self::assertFalse($Field->isPublic());
        self::assertTrue($Field->isSystem());
        self::assertTrue($Field->isStandard());
        self::assertTrue($Field->isRequired());
        self::assertTrue($Field->showInDetails());
        self::assertSame('fallback', $Field->getDefaultValue());
        self::assertSame('steel', $Field->getValue());
        self::assertSame('steel', $Field->getValueByLocale());
        self::assertSame(['trim' => true], $Field->getOptions());
        self::assertTrue($Field->getOption('trim'));
        self::assertFalse($Field->getOption('missing'));
        self::assertFalse($Field->isCustomField());

        $ForcedPublic = new Input(4, ['public' => false, 'name' => 'description']);
        self::assertTrue($ForcedPublic->isPublic());
    }

    public function testMutableValueDefaultAndAssignmentStateIsObservable(): void
    {
        $Field = new Input(990110, ['name' => 'state']);

        $Field->setDefaultValue(123);
        self::assertSame('123', $Field->getDefaultValue());
        self::assertSame('123', $Field->getValue());

        $Field->setValue(456);
        self::assertSame('456', $Field->getValue());
        self::assertFalse($Field->isEmpty());
        self::assertSame('456', $Field->getSearchCacheValue());

        $Field->clearValue();
        self::assertSame('123', $Field->getValue());
        self::assertNull($Field->getSearchCacheValue());

        $Field->clearDefaultValue();
        self::assertNull($Field->getDefaultValue());
        self::assertNull($Field->getValue());

        $Field->setName('renamed');
        $Field->setPublicStatus(false);
        $Field->setOwnFieldStatus(true);
        $Field->setUnassignedStatus(true);
        $Field->setShowInDetailsStatus(true);
        self::assertSame('renamed', $Field->getName());
        self::assertFalse($Field->isPublic());
        self::assertTrue($Field->isOwnField());
        self::assertTrue($Field->isUnassigned());
        self::assertTrue($Field->showInDetails());
    }

    public function testLocalizedPrefixSuffixAndAttributesAreNormalized(): void
    {
        $Field = new Input(990111, ['name' => 'dimensions']);
        $Locale = new \QUI\Locale();
        $Locale->setCurrent('de');
        $Field->setAttribute('prefix', json_encode(['de' => 'ab ', 'en' => 'from ']));
        $Field->setAttribute('suffix', json_encode(['de' => ' kg', 'en' => ' lb']));
        $Field->setAttribute('priority', '<b>7</b>');
        $Field->setAttribute('requiredField', true);
        $Field->setAttribute('publicField', false);
        $Field->setAttribute('showInDetails', true);
        $Field->setAttribute('standardField', true);
        $Field->setAttribute('systemField', true);

        self::assertSame('ab ', $Field->getPrefix($Locale));
        self::assertSame(' kg', $Field->getSuffix($Locale));
        self::assertSame('7', $Field->getAttribute('priority'));
        self::assertTrue($Field->isRequired());
        self::assertFalse($Field->isPublic());
        self::assertTrue($Field->showInDetails());
        self::assertTrue($Field->isStandard());
        self::assertFalse($Field->isSystem());
    }

    public function testViewsPricesSearchMetadataAndSerializationExposeFieldContract(): void
    {
        $Field = new Input(990112, [
            'name' => 'serial',
            'value' => 'visible',
            'public' => true
        ]);

        self::assertInstanceOf(View::class, $Field->getBackendView());
        self::assertInstanceOf(View::class, $Field->getFrontendView());
        self::assertInstanceOf(View::class, $Field->getView());
        self::assertInstanceOf(View::class, $Field->getValueView());
        self::assertSame(0.0, $Field->getPrice()->value());
        self::assertSame('TEXT', $Field->getColumnType());
        self::assertSame('text', $Field->getDefaultSearchType());
        self::assertContains('text', $Field->getSearchTypes());
        self::assertIsInt($Field->getSearchDataType());
        self::assertTrue($Field->hasViewPermission());

        $ProductData = $Field->toProductArray();
        self::assertSame(990112, $ProductData['id']);
        self::assertSame('visible', $ProductData['value']);
        self::assertSame('Input', $ProductData['type']);

        $Unique = $Field->createUniqueField();
        self::assertInstanceOf(UniqueField::class, $Unique);
        self::assertSame('serial', $Unique->getName());
        self::assertSame('visible', $Unique->getValue());

        $NotSearchable = new CheckboxInput(990113, ['name' => 'accept']);
        self::assertFalse($NotSearchable->isSearchable());
        self::assertSame([], $NotSearchable->getSearchTypes());
        self::assertNull($NotSearchable->getDefaultSearchType());
    }

    public function testTitlesDescriptionsAndMissingLocalizedDecorationsUseFallbacks(): void
    {
        $Field = new Input(990114, ['name' => 'locale']);
        $German = new \QUI\Locale();
        $German->setCurrent('de');
        $English = new \QUI\Locale();
        $English->setCurrent('en');
        $Field->setAttribute('prefix', json_encode(['de' => 'ab ']));
        $Field->setAttribute('suffix', json_encode(['de' => ' kg']));

        self::assertSame('[quiqqer/products] products.field.990114.title', $Field->getTitle($German));
        self::assertSame('[quiqqer/products] products.field.990114.title', $Field->getWorkingTitle($German));
        self::assertSame('', $Field->getDescription($German));
        self::assertSame(json_encode(['de' => 'ab ']), $Field->getPrefix($English));
        self::assertSame(json_encode(['de' => ' kg']), $Field->getSuffix($English));
    }

    public function testCalculatedValueRangesCoverFractionsUnitsAndMagnitudeSteps(): void
    {
        $Field = new Input(990115, ['name' => 'range']);

        self::assertSame([0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0, 2.0], $Field->calculateValueRange(0.2, 1.2));
        self::assertSame([1, 2.0, 3.0], $Field->calculateValueRange(1, 3));
        self::assertSame([140, 200.0, 300.0], $Field->calculateValueRange(144, 255));
    }
}
