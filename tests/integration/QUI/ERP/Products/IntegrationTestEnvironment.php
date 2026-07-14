<?php

namespace QUITests\ERP\Products\Integration;

use QUI;
use QUI\ERP\Areas\Area;
use QUI\ERP\Areas\Handler;
use QUI\ERP\Areas\Utils;
use QUI\ERP\Currency\Handler as CurrencyHandler;
use QUI\ERP\Tax\Handler as TaxHandler;
use QUI\ERP\Tax\Utils as TaxUtils;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class IntegrationTestEnvironment
{
    public const PREFIX = 'phpunit-products-';

    private static ?int $createdAreaId = null;
    private static ?int $createdTaxEntryId = null;
    private static ?int $createdTaxTypeId = null;
    /** @var array<mixed>|false|null */
    private static array | false | null $originalTaxGroups = null;
    /** @var array<mixed>|false|null */
    private static array | false | null $originalTaxTypes = null;
    private static bool $createdCurrency = false;
    private static bool $cleanupRegistered = false;

    public static function ensureDefaults(): Area
    {
        self::registerCleanup();
        self::ensureDefaultCurrency();
        $Country = QUI\ERP\Defaults::getCountry();
        $Area = Utils::getAreaByCountry($Country);

        if (!$Area instanceof Area) {
            $Area = self::createDefaultArea($Country->getCode());
        }

        self::ensureDefaultTax($Area);

        return $Area;
    }

    private static function createDefaultArea(string $countryCode): Area
    {
        $Areas = new Handler();

        try {
            $Connection = QUI::getDataBaseConnection();
            $Connection->insert(QUI::getDBTableName('areas'), [
                'countries' => $countryCode,
                'data' => json_encode([
                    'importLocale' => self::PREFIX . 'default-area'
                ], JSON_THROW_ON_ERROR)
            ]);
            self::$createdAreaId = (int)$Connection->lastInsertId();
            $Area = $Areas->getChild(self::$createdAreaId);
        } catch (Throwable $Exception) {
            self::cleanup();

            throw new RuntimeException(
                'The PHPUnit ERP area could not be created: ' . $Exception->getMessage(),
                0,
                $Exception
            );
        }

        return $Area;
    }

    public static function cleanup(): void
    {
        self::cleanupTax();

        if (self::$createdAreaId !== null) {
            $areaId = self::$createdAreaId;

            try {
                ProjectTestHelper::runAsSystemUser(static function () use ($areaId): void {
                    (new Handler())->getChild($areaId)->delete();
                });
            } catch (Throwable) {
                try {
                    QUI::getDataBaseConnection()->delete(QUI::getDBTableName('areas'), ['id' => $areaId]);
                } catch (Throwable) {
                    // Cleanup must never hide the actual test result or shutdown reason.
                }
            } finally {
                self::$createdAreaId = null;
            }
        }

        if (self::$createdCurrency) {
            try {
                ProjectTestHelper::runAsSystemUser(static function (): void {
                    CurrencyHandler::deleteCurrency('EUR');
                });
            } catch (Throwable) {
                // Cleanup must never hide the actual test result or shutdown reason.
            } finally {
                self::$createdCurrency = false;
            }
        }
    }

    private static function ensureDefaultCurrency(): void
    {
        $eurExisted = CurrencyHandler::existCurrency('EUR');

        ProjectTestHelper::runAsSystemUser(static function (): void {
            QUI\ERP\Defaults::getCurrency();
        });

        self::$createdCurrency = !$eurExisted && CurrencyHandler::existCurrency('EUR');
    }

    private static function ensureDefaultTax(Area $Area): void
    {
        try {
            $TaxType = TaxUtils::getTaxTypeByArea($Area);
            $TaxEntry = TaxUtils::getTaxEntry($TaxType, $Area);

            if ($TaxEntry->isActive()) {
                return;
            }
        } catch (Throwable) {
        }

        $Taxes = new TaxHandler();
        $Config = $Taxes->getConfig();
        self::$originalTaxGroups = $Config->getSection('taxgroups');
        self::$originalTaxTypes = $Config->getSection('taxtypes');

        try {
            ProjectTestHelper::runAsSystemUser(static function () use ($Taxes, $Config, $Area): void {
                $TaxType = $Taxes->createTaxType();
                self::$createdTaxTypeId = $TaxType->getId();
                $groups = self::$originalTaxGroups;

                if (!is_array($groups)) {
                    $groups = [];
                }

                $groupTypes = array_filter(explode(',', (string)($groups[0] ?? '')), 'strlen');
                $groupTypes[] = (string)$TaxType->getId();
                $groups[0] = implode(',', array_unique($groupTypes));
                $Config->setSection('taxgroups', $groups);
                $Config->save();

                $TaxEntry = $Taxes->createChild([
                    'areaId' => $Area->getId(),
                    'taxTypeId' => $TaxType->getId(),
                    'taxGroupId' => 0,
                    'vat' => 19,
                    'active' => 1,
                    'euvat' => 1
                ]);
                self::$createdTaxEntryId = (int)$TaxEntry->getId();
            });
            self::clearTaxRuntimeCache();
        } catch (Throwable $Exception) {
            self::cleanupTax();
            throw $Exception;
        }
    }

    private static function cleanupTax(): void
    {
        if (
            self::$createdTaxEntryId === null
            && self::$createdTaxTypeId === null
            && self::$originalTaxGroups === null
            && self::$originalTaxTypes === null
        ) {
            return;
        }

        $Taxes = new TaxHandler();

        if (self::$createdTaxEntryId !== null) {
            self::runCleanupStep(static function () use ($Taxes): void {
                try {
                    ProjectTestHelper::runAsSystemUser(static function () use ($Taxes): void {
                        $Taxes->getChild(self::$createdTaxEntryId ?? 0)->delete();
                    });
                } catch (Throwable) {
                    QUI::getDataBaseConnection()->delete(
                        QUI\Utils\Doctrine::quoteIdentifier($Taxes->getDataBaseTableName()),
                        ['id' => self::$createdTaxEntryId]
                    );
                }
            });
        }

        if (self::$createdTaxTypeId !== null) {
            self::runCleanupStep(static function () use ($Taxes): void {
                ProjectTestHelper::runAsSystemUser(static function () use ($Taxes): void {
                    $Taxes->deleteTaxType(self::$createdTaxTypeId ?? 0);
                });
            });
        }

        self::runCleanupStep(static function () use ($Taxes): void {
            $Config = $Taxes->getConfig();

            if (self::$originalTaxGroups === false) {
                $Config->del('taxgroups');
            } elseif (is_array(self::$originalTaxGroups)) {
                $Config->setSection('taxgroups', self::$originalTaxGroups);
            }

            if (self::$originalTaxTypes === false) {
                $Config->del('taxtypes');
            } elseif (is_array(self::$originalTaxTypes)) {
                $Config->setSection('taxtypes', self::$originalTaxTypes);
            }

            $Config->save();
        });

        self::$createdTaxEntryId = null;
        self::$createdTaxTypeId = null;
        self::$originalTaxGroups = null;
        self::$originalTaxTypes = null;
        self::clearTaxRuntimeCache();
    }

    private static function clearTaxRuntimeCache(): void
    {
        try {
            $Property = new ReflectionProperty(TaxUtils::class, 'userTaxes');
            $Property->setValue(null, []);
        } catch (Throwable) {
        }
    }

    private static function runCleanupStep(callable $Callback): void
    {
        try {
            $Callback();
        } catch (Throwable) {
            // Cleanup must never hide the actual test result or shutdown reason.
        }
    }

    private static function registerCleanup(): void
    {
        if (self::$cleanupRegistered) {
            return;
        }

        self::$cleanupRegistered = true;
        QUI::getEvents()->addEvent(
            QUI\System\TestCleanup::EVENT,
            static function (): void {
                self::cleanup();
            }
        );
    }
}
