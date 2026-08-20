<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Textarea;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

class TextareaTest extends TestCase
{
    public function testCleanupKeepsTextAndNumericValuesButRejectsCollections(): void
    {
        $Field = new Textarea(991032, ['name' => 'textarea']);

        self::assertSame('multiline text', $Field->cleanup('multiline text'));
        self::assertSame('42', $Field->cleanup(42));
        self::assertSame('12.5', $Field->cleanup(12.5));
        self::assertNull($Field->cleanup([]));
        self::assertNull($Field->cleanup(new \stdClass()));
    }

    public function testValidationAcceptsScalarContentAndRejectsCollections(): void
    {
        $Field = new Textarea(991032, ['name' => 'textarea']);

        $Field->validate(null);
        $Field->validate([]);
        $Field->validate('content');
        $Field->validate(42);

        foreach ([new \stdClass()] as $invalidValue) {
            try {
                $Field->validate($invalidValue);
                self::fail('Textarea collections must be rejected.');
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
        }
    }

    public function testViewsControlAndSearchMetadataDescribeTextareaBehavior(): void
    {
        $Field = new Textarea(991032, [
            'name' => 'textarea',
            'public' => true,
            'value' => 'visible text'
        ]);

        self::assertInstanceOf(View::class, $Field->getBackendView());
        self::assertStringContainsString('visible text', $Field->getFrontendView()->create());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Textarea',
            $Field->getJavaScriptControl()
        );
        self::assertSame([
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE
        ], $Field->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_TEXT, $Field->getDefaultSearchType());
    }
}
