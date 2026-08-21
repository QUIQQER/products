<?php

namespace QUITests\ERP\Products\Integration\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Model;
use QUI\ERP\Products\Product\Exception as ProductException;
use QUI\Permissions\Exception as PermissionException;
use QUITests\ERP\Products\Fixtures\TestUser;
use ReflectionProperty;

class ProductModelAdvancedBehaviorTest extends ProductIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    public function testLocalizedValuesPriorityAndFieldSourcesReflectTheCurrentFields(): void
    {
        $Product = ProductTestHelper::createProduct('localized-product', 17.25);
        $Locale = new QUI\Locale();
        $Locale->setCurrent('de');

        $Product->getField(Fields::FIELD_TITLE)->setValue([
            'de' => '',
            'en' => 'English fallback'
        ]);
        $Product->getField(Fields::FIELD_SHORT_DESC)->setValue([
            'de' => 'Kurzbeschreibung',
            'en' => 'Short description'
        ]);
        $Product->getField(Fields::FIELD_CONTENT)->setValue([
            'de' => 'Sprachunabhängiger Inhalt',
            'en' => 'Independent content'
        ]);
        $Product->getPriorityField()->setValue(37);

        self::assertSame('English fallback', $Product->getTitle($Locale));
        self::assertSame('Kurzbeschreibung', $Product->getDescription($Locale));
        self::assertSame('Sprachunabhängiger Inhalt', $Product->getContent($Locale));
        self::assertSame(37, $Product->getPriority());
        self::assertSame(37, $Product->getFieldValue('FIELD_PRIORITY'));
        self::assertSame(
            'Kurzbeschreibung',
            $Product->getFieldValueByLocale(Fields::FIELD_SHORT_DESC, $Locale)
        );

        $sources = $Product->getFieldSource(Fields::FIELD_PRICE);
        self::assertNotEmpty($sources);
        self::assertContains(
            QUI::getLocale()->get('quiqqer/products', 'systemField'),
            $sources
        );
    }

    public function testOfferPriceChangesDisplayedAndOriginalPrices(): void
    {
        $Product = ProductTestHelper::createProduct('offer-product', 40.0);
        $Product->getField(Fields::FIELD_PRICE_OFFER)->setValue(31.5);

        self::assertTrue($Product->hasOfferPrice());
        self::assertSame(31.5, $Product->getPrice()->value());
        self::assertSame(31.5, $Product->getNettoPrice()->value());
        self::assertSame(40.0, $Product->getOriginalPrice()->getValue());
        self::assertSame(
            31.5,
            $Product->getCalculatedPrice(Fields::FIELD_PRICE_OFFER)->getValue()
        );

        $Product->getField(Fields::FIELD_PRICE_OFFER)->setValue(null);
        self::assertFalse($Product->hasOfferPrice());
    }

    public function testDuplicateArticleNumberPreventsSecondProductActivation(): void
    {
        $First = ProductTestHelper::createProduct('duplicate-number-first');
        $Second = ProductTestHelper::createProduct('duplicate-number-second');
        $articleNo = (string)$First->getFieldValue(Fields::FIELD_PRODUCT_NO);
        $Second->getField(Fields::FIELD_PRODUCT_NO)->setValue($articleNo);
        $DuplicateCheck = new ReflectionProperty(Products::class, 'checkDuplicateArticleNo');
        $originalSetting = $DuplicateCheck->getValue();
        $DuplicateCheck->setValue(null, true);

        try {
            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($First): void {
                $First->activate($SystemUser);
            });

            try {
                ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Second): void {
                    $Second->activate($SystemUser);
                });
                self::fail('A second active product with the same article number must be rejected.');
            } catch (ProductException $Exception) {
                self::assertSame(400, $Exception->getCode());
                self::assertSame($Second->getId(), $Exception->getContext()['updateProduct']);
                self::assertSame(
                    QUI::getLocale()->get('quiqqer/products', 'exception.duplicate_article_no', [
                        'articleNo' => $articleNo,
                        'otherProductId' => $First->getId()
                    ]),
                    $Exception->getMessage()
                );
            }

            self::assertTrue($First->isActive());
            self::assertFalse($Second->isActive());
        } finally {
            $DuplicateCheck->setValue(null, $originalSetting);
        }
    }

    public function testInvalidConstructionAndInactiveFrontendViewReportNotFound(): void
    {
        try {
            new Model(999999, []);
            self::fail('A model without persisted product data must not be constructed.');
        } catch (ProductException $Exception) {
            self::assertSame(404, $Exception->getCode());
            self::assertSame(999999, $Exception->getContext()['id']);
        }

        $Product = ProductTestHelper::createProduct('inactive-frontend-view');

        try {
            $Product->getViewFrontend();
            self::fail('An inactive product must not expose a frontend view.');
        } catch (ProductException $Exception) {
            self::assertSame(404, $Exception->getCode());
            self::assertSame($Product->getId(), $Exception->getContext()['id']);
            self::assertSame('frontend', $Exception->getContext()['view']);
        }
    }

    public function testMissingTitleAndWrongMediaFolderFieldHaveStableFallbacks(): void
    {
        $Product = ProductTestHelper::createProduct('fallback-product');
        $Product->getField(Fields::FIELD_TITLE)->setValue(['de' => '', 'en' => '']);

        self::assertSame('', $Product->getTitle());

        $this->expectException(ProductException::class);
        $Product->createMediaFolder(Fields::FIELD_PRICE);
    }

    public function testMaximumQuantityCanBeExtendedAndSurvivesFaultyListener(): void
    {
        $Product = ProductTestHelper::createProduct('maximum-quantity-product');
        $SetQuantity = static function ($Product, &$quantity): void {
            $quantity = 6;
        };
        QUI::getEvents()->addEvent('onQuiqqerProductsProductGetMaxQuantity', $SetQuantity);

        try {
            self::assertSame(6, $Product->getMaximumQuantity());
        } finally {
            QUI::getEvents()->removeEvent('onQuiqqerProductsProductGetMaxQuantity', $SetQuantity);
        }

        $Throw = static function (): void {
            throw new QUI\Exception('phpunit maximum quantity listener');
        };
        QUI::getEvents()->addEvent('onQuiqqerProductsProductGetMaxQuantity', $Throw);

        try {
            self::assertTrue($Product->getMaximumQuantity());
        } finally {
            QUI::getEvents()->removeEvent('onQuiqqerProductsProductGetMaxQuantity', $Throw);
        }
    }

    public function testUniqueProductRuntimeCacheCanBeCreatedAndInvalidated(): void
    {
        $Product = ProductTestHelper::createProduct('cached-unique-product', 9.75);
        $originalSetting = Products::$useRuntimeCacheForUniqueProducts;
        Products::$useRuntimeCacheForUniqueProducts = true;
        $User = new TestUser(TestUser::TYPE_NETTO);

        try {
            $First = $Product->createUniqueProduct($User);
            $Second = $Product->createUniqueProduct($User);

            self::assertNotSame($First->getUuid(), $Second->getUuid());
            self::assertSame($First->getTitle(), $Second->getTitle());
            self::assertSame($User->getUUID(), $Second->getAttributes()['uid']);
            self::assertSame(9.75, $Second->getUnitPrice()->value());

            $Product->clearUniqueProductCache($User);
            $Third = $Product->createUniqueProduct($User);
            self::assertNotSame($Second->getUuid(), $Third->getUuid());
            self::assertSame('cached-unique-product', $Third->getTitle());
        } finally {
            $Product->clearUniqueProductCache($User);
            Products::$useRuntimeCacheForUniqueProducts = $originalSetting;
        }
    }

    public function testProductPermissionsAllowConfiguredUserAndRejectAnotherUser(): void
    {
        $Product = ProductTestHelper::createProduct('permission-product');
        $Allowed = new TestUser(TestUser::TYPE_NETTO);
        $Denied = new TestUser(TestUser::TYPE_COMPANY);
        $UsePermissions = new ReflectionProperty(Products::class, 'usePermissions');
        $Permissions = new ReflectionProperty(Model::class, 'permissions');
        $originalUsePermissions = $UsePermissions->getValue();
        $originalPermissions = $Permissions->getValue($Product);
        $UsePermissions->setValue(null, true);
        $Permissions->setValue($Product, [
            'view' => 'u' . $Allowed->getUUID(),
            'edit' => 'u' . $Allowed->getUUID()
        ]);

        try {
            self::assertTrue($Product->hasPermission('view', $Allowed));
            self::assertFalse($Product->hasPermission('view', $Denied));
            self::assertTrue($Product->hasPermission('unconfigured', $Denied));

            try {
                $Product->checkPermission('view', $Denied);
                self::fail('A user outside the configured user/group list must be rejected.');
            } catch (PermissionException $Exception) {
                self::assertSame(403, $Exception->getCode());
                self::assertSame($Denied->getUUID(), $Exception->getContext()['userid']);
            }

            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                $Product->clearPermission('edit', $SystemUser);
            });
            self::assertSame([], $Product->getPermissions()['edit']);

            ProductTestHelper::runAsSystemUser(static function ($SystemUser) use ($Product): void {
                $Product->clearPermissions($SystemUser);
            });
            self::assertSame([], $Product->getPermissions());
        } finally {
            $Permissions->setValue($Product, $originalPermissions);
            $UsePermissions->setValue(null, $originalUsePermissions);
        }
    }

    public function testProductCurrencyOverrideIsReturnedUnchanged(): void
    {
        $Product = ProductTestHelper::createProduct('currency-product');
        $Currency = QUI\ERP\Currency\Handler::getCurrency('EUR');

        self::assertNull($Product->getCurrency());
        $Product->setCurrency($Currency);
        self::assertSame($Currency, $Product->getCurrency());
    }

    public function testForcedPriceFactorUpdatesOnlyExistingFieldsWithAValue(): void
    {
        $Product = ProductTestHelper::createProduct('forced-price-factor', 12.34);
        $PriceFactorSettings = new ReflectionProperty(Fields::class, 'priceFactorSettings');
        $originalSettings = $PriceFactorSettings->getValue();
        $PriceFactorSettings->setValue(null, [
            999999 => [
                'sourceFieldId' => Fields::FIELD_PRICE,
                'multiplier' => 2,
                'updateOnSave' => true
            ],
            Fields::FIELD_PRICE_RETAIL => [
                'sourceFieldId' => 999998,
                'multiplier' => 2,
                'updateOnSave' => true
            ],
            Fields::FIELD_PRICE_OFFER => [
                'sourceFieldId' => Fields::FIELD_PRICE,
                'multiplier' => 2,
                'updateOnSave' => false
            ]
        ]);

        try {
            $Product->validateFields();
            self::assertNull($Product->getFieldValue(Fields::FIELD_PRICE_OFFER));

            $Product->setForcePriceFieldFactorUse(true);
            $Product->validateFields();
            self::assertSame(24.68, $Product->getFieldValue(Fields::FIELD_PRICE_OFFER));

            $Product->getField(Fields::FIELD_PRICE)->setValue(0);
            $Product->getField(Fields::FIELD_PRICE_OFFER)->setValue(5.0);
            $Product->validateFields();
            self::assertSame(5.0, $Product->getFieldValue(Fields::FIELD_PRICE_OFFER));
        } finally {
            $PriceFactorSettings->setValue(null, $originalSettings);
        }
    }

    #[DataProvider('priceFactorRoundingScenarios')]
    public function testPriceFactorRoundingProducesExpectedBusinessPrice(
        string $roundingType,
        ?string $customDecimals,
        ?string $surchargePriority,
        float $surcharge,
        float $vat,
        float $expectedPrice
    ): void {
        $Product = ProductTestHelper::createProduct('rounded-price-' . $roundingType, 12.34);
        $PriceFactorSettings = new ReflectionProperty(Fields::class, 'priceFactorSettings');
        $originalSettings = $PriceFactorSettings->getValue();
        $rounding = ['type' => $roundingType];

        if ($customDecimals !== null) {
            $rounding['custom'] = $customDecimals;
        }

        if ($vat > 0) {
            $rounding['vat'] = $vat;
        }

        $PriceFactorSettings->setValue(null, [
            Fields::FIELD_PRICE_OFFER => [
                'sourceFieldId' => Fields::FIELD_PRICE,
                'multiplier' => 2,
                'updateOnSave' => true,
                'fixedSurchargeAmount' => $surcharge,
                'fixedSurchargePriority' => $surchargePriority,
                'rounding' => $rounding
            ]
        ]);

        try {
            $Product->validateFields();

            self::assertEqualsWithDelta(
                $expectedPrice,
                (float)$Product->getFieldValue(Fields::FIELD_PRICE_OFFER),
                0.000001
            );
        } finally {
            $PriceFactorSettings->setValue(null, $originalSettings);
        }
    }

    /**
     * @return array<string, array{string, ?string, ?string, float, float, float}>
     */
    public static function priceFactorRoundingScenarios(): array
    {
        return [
            'round up with configured decimals' => ['up', '49', null, 0.0, 0.0, 30.49],
            'round up to nine then add surcharge' => ['up_9', '49', 'afterRounding', 2.0, 0.0, 31.49],
            'round down preserving decimals' => ['down', null, null, 0.0, 0.0, 20.68],
            'round down to nine' => ['down_9', '95', null, 0.0, 0.0, 19.95],
            'commercial rounding' => ['commercial', '95', null, 0.0, 0.0, 20.95],
            'commercial rounding to nine' => ['commercial_9', null, null, 0.0, 0.0, 19.68],
            'whole price after surcharge' => ['commercial_decimals', null, 'beforeRounding', 1.0, 0.0, 26.0],
            'single decimal gross rounding' => [
                'commercial_decimals_single',
                null,
                null,
                0.0,
                19.0,
                24.705882352941
            ]
        ];
    }
}
