<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\UserInput;
use QUI\ERP\Products\Field\Types\UserInputBackendView;
use QUI\ERP\Products\Field\Types\UserInputFrontendView;

class UserInputTest extends TestCase
{
    public function testCleanupRemovesMarkupPunctuationAndLimitsLength(): void
    {
        $Field = new UserInput(9103, [
            'options' => [
                'inputType' => 'textarea',
                'maxCharacters' => 12
            ]
        ]);

        $cleaned = $Field->cleanup('<b>Hello!</b> World');

        self::assertSame('bHellob Worl', $cleaned);
        self::assertSame(12, mb_strlen($cleaned));
        self::assertStringNotContainsString('<', $cleaned);
        self::assertStringNotContainsString('!', $cleaned);
    }

    #[DataProvider('cleanupValues')]
    public function testCleanupNormalizesSupportedScalarValues(mixed $value, ?string $expected): void
    {
        $Field = new UserInput(9103, ['options' => ['maxCharacters' => 100]]);

        self::assertSame($expected, $Field->cleanup($value));
    }

    public static function cleanupValues(): iterable
    {
        yield 'plain text' => ['Customer text', 'Customer text'];
        yield 'json encoded text' => ['"JSON text!"', 'JSON text'];
        yield 'integer' => [12345, '12345'];
        yield 'zero is empty' => [0, null];
        yield 'empty string' => ['', null];
        yield 'unsupported collection' => [[], null];
    }

    public function testValidationAcceptsScalarInputAndRejectsCollections(): void
    {
        $Field = new UserInput(9103, []);
        $Field->validate('Text');
        $Field->validate(123);
        $Field->validate('');

        $this->expectException(Exception::class);
        $Field->validate(['not' => 'a scalar']);
    }

    public function testStoredUserInputAndViewsExposeTheConfiguredField(): void
    {
        $Field = new UserInput(9103, [
            'title' => ['de' => 'Gravur'],
            'value' => 'Initials',
            'options' => ['inputType' => 'input_inline', 'maxCharacters' => 20]
        ]);

        self::assertSame('Initials', $Field->getUserInput());
        self::assertInstanceOf(UserInputFrontendView::class, $Field->getFrontendView());
        self::assertInstanceOf(UserInputBackendView::class, $Field->getBackendView());
        self::assertSame(9103, $Field->getFrontendView()->getId());
        self::assertSame(9103, $Field->getBackendView()->getId());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/UserInput',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/UserInputSettings',
            $Field->getJavaScriptSettings()
        );

        $Field->setValue(null);
        self::assertSame('', $Field->getUserInput());
    }
}
