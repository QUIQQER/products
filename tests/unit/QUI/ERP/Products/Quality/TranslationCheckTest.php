<?php

namespace QUITests\ERP\Products\Unit\Quality;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Types\Input;
use QUI\ERP\Products\Field\Types\InputMultiLang;
use QUI\ERP\Products\Field\Types\TextareaMultiLang;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Model;
use QUI\ERP\Products\Quality\TranslationCheck;

class TranslationCheckTest extends TestCase
{
    public function testMissingTranslationsAreReportedWithoutUsingTheDisplayFallback(): void
    {
        $Title = new InputMultiLang(Fields::FIELD_TITLE, [
            'title' => ['de' => 'Titel'],
            'value' => ['de' => 'Produkt', 'en' => " \t"]
        ]);
        $Product = $this->createMock(Model::class);
        $Product->method('getFields')->willReturn([$Title]);

        self::assertSame([
            [
                'fieldId' => Fields::FIELD_TITLE,
                'fieldTitle' => 'Titel',
                'languages' => ['en', 'fr']
            ]
        ], (new TranslationCheck())->check($Product, ['de', 'en', 'fr']));
        self::assertSame(['de' => 'Produkt', 'en' => " \t"], $Title->getValue());
    }

    public function testEmptyOptionalAndNonTranslatedFieldsAreIgnored(): void
    {
        $Product = $this->createMock(Model::class);
        $Product->method('getFields')->willReturn([
            new Input(9000, ['value' => 'Untranslated input']),
            new InputMultiLang(9001, ['value' => ['de' => '', 'en' => ' ']]),
            new TextareaMultiLang(9002, [
                'options' => ['html' => 1],
                'value' => ['de' => '<p>&nbsp;<br></p>']
            ]),
            new InputMultiLang(9003, ['value' => ['de' => '0', 'en' => '0']])
        ]);

        self::assertSame([], (new TranslationCheck())->check($Product, ['de', 'en']));
    }

    public function testEmptyTitleAndRequiredFieldsAreReportedInEveryRequestedLanguage(): void
    {
        $Product = $this->createMock(Model::class);
        $Product->method('getFields')->willReturn([
            new InputMultiLang(Fields::FIELD_TITLE),
            new TextareaMultiLang(9002, ['required' => true])
        ]);

        $issues = (new TranslationCheck())->check($Product, ['de', 'en']);

        self::assertSame([Fields::FIELD_TITLE, 9002], array_column($issues, 'fieldId'));
        self::assertSame([['de', 'en'], ['de', 'en']], array_column($issues, 'languages'));
    }

    public function testEmptyHtmlIsMissingButMediaAndLiteralInputAreContent(): void
    {
        $Product = $this->createMock(Model::class);
        $Product->method('getFields')->willReturn([
            new TextareaMultiLang(9001, [
                'options' => ['html' => 1],
                'value' => ['de' => '<p>Beschreibung</p>', 'en' => '<p>&nbsp;​<br></p>']
            ]),
            new TextareaMultiLang(9002, [
                'options' => ['html' => 1],
                'value' => ['de' => '<img src="de.png">', 'en' => '<img src="en.png">']
            ]),
            new InputMultiLang(9003, ['value' => ['de' => '<text>', 'en' => '<text>']])
        ]);

        $issues = (new TranslationCheck())->check($Product, ['de', 'en']);

        self::assertCount(1, $issues);
        self::assertSame(9001, $issues[0]['fieldId']);
        self::assertSame(['en'], $issues[0]['languages']);
    }

    public function testOnlyRequestedLanguagesAreChecked(): void
    {
        $Product = $this->createMock(Model::class);
        $Product->method('getFields')->willReturn([
            new InputMultiLang(Fields::FIELD_TITLE, ['value' => ['de' => 'Produkt']])
        ]);

        self::assertSame([], (new TranslationCheck())->check($Product, ['de']));
    }
}
