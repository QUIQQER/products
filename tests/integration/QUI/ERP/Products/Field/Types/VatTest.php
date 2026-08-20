<?php

namespace QUITests\ERP\Products\Integration\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Vat;
use QUI\ERP\Products\Field\Types\VatFrontendView;
use QUI\ERP\Tax\Utils;
use QUITests\ERP\Products\Integration\IntegrationTestEnvironment as TestEnvironment;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;

class VatTest extends ProductIntegrationTestCase
{
    public function testCleanupNormalizesEmptyNumericAndPrefixedValues(): void
    {
        $Vat = new Vat(9020, ['name' => 'vat']);

        self::assertSame(-1, $Vat->cleanup(null));
        self::assertSame(-1, $Vat->cleanup(0));
        self::assertSame(7, $Vat->cleanup(7));
        self::assertSame(7, $Vat->cleanup('tax:7'));
        self::assertSame(-1, $Vat->cleanup('tax:'));
    }

    public function testValidationAcceptsConfiguredTaxTypeAndRejectsInvalidValues(): void
    {
        $Area = TestEnvironment::ensureDefaults();
        $taxTypeId = Utils::getTaxTypeByArea($Area)->getId();
        $Vat = new Vat(9020, ['name' => 'vat']);

        $Vat->validate(null);
        $Vat->validate(-1);
        $Vat->validate('tax:' . $taxTypeId);

        foreach (['invalid', 'tax:', 'tax:invalid', 'tax:999999'] as $value) {
            try {
                $Vat->validate($value);
                self::fail("Invalid VAT value '$value' must be rejected.");
            } catch (Exception $Exception) {
                self::assertStringContainsString('exception.field.invalid', $Exception->getMessage());
            }
        }
    }

    public function testFrontendViewRendersConfiguredTaxForSessionUser(): void
    {
        $Area = TestEnvironment::ensureDefaults();
        $TaxType = Utils::getTaxTypeByArea($Area);
        $Vat = new Vat(9020, [
            'name' => 'vat',
            'public' => true,
            'value' => $TaxType->getId()
        ]);

        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Vat',
            $Vat->getJavaScriptControl()
        );
        self::assertInstanceOf(VatFrontendView::class, $Vat->getFrontendView());

        $html = ProductTestHelper::runAsSystemUser(
            static fn (): string => $Vat->getFrontendView()->create()
        );

        self::assertStringContainsString('quiqqer-product-field-value', $html);
        self::assertStringNotContainsString('>---</div>', $html);
    }
}
