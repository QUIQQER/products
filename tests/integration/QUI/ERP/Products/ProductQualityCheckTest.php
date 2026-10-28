<?php

namespace QUITests\ERP\Products\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI;
use QUI\ERP\Products\Crons;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\AbstractType;
use QUI\ERP\Products\Utils\Tables;
use QUI\Mail\Manager as MailManager;
use QUI\Messages\Handler as MessagesHandler;
use QUI\Users\Manager as UsersManager;
use QUI\Users\User;
use QUI\Utils\Text\XML;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class ProductQualityCheckTest extends ProductIntegrationTestCase
{
    private ?MailManager $originalMailManager;
    private ?MessagesHandler $originalMessagesHandler;
    private UsersManager $originalUsers;
    private ?QUI\Config $originalConfig;

    /** @var array<string, mixed> */
    private array $originalLocales;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalMailManager = QUI::$MailManager;
        $this->originalMessagesHandler = QUI::$MessageHandler;
        $this->originalUsers = QUI::getUsers();
        QUI::conf('mail', 'admin_mail');
        $this->originalConfig = QUI::$Conf;
        QUI::$Conf = clone $this->originalConfig;
        QUI::$Conf->set('mail', 'admin_mail', 'admin@example.invalid');
        $this->originalLocales = (new ReflectionProperty(QUI\LocaleRuntimeCache::class, 'languages'))->getValue();
        $Document = XML::getDomFromXml(dirname(__DIR__, 5) . '/locale/de.xml');
        $groups = XML::getLocaleGroupsFromDom($Document);
        $loaded = 0;

        foreach ($groups as $group) {
            if ($group['group'] !== 'quiqqer/products') {
                continue;
            }

            foreach ($group['locales'] as $entry) {
                if (!str_starts_with($entry['name'], 'cron.productQualityCheck.')) {
                    continue;
                }

                QUI::getLocale()->set('de', 'quiqqer/products', $entry['name'], $entry['de']);
                $loaded++;
            }
        }

        self::assertSame(17, $loaded);

        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::never())->method('send');
        QUI::$MailManager = $Mail;
        $Messages = $this->createMock(MessagesHandler::class);
        $Messages->expects(self::never())->method('sendAttention');
        QUI::$MessageHandler = $Messages;
    }

    protected function tearDown(): void
    {
        QUI::$MailManager = $this->originalMailManager;
        QUI::$MessageHandler = $this->originalMessagesHandler;
        QUI::$Users = $this->originalUsers;
        QUI::$Conf = $this->originalConfig;
        (new ReflectionProperty(QUI\LocaleRuntimeCache::class, 'languages'))->setValue(null, $this->originalLocales);
        parent::tearDown();
    }

    public function testEmailReportsStoredMissingTranslationsAndLeavesProductsUnchanged(): void
    {
        $Product = $this->createProductWithMissingTranslations('quality-product');
        $id = $Product->getId();
        $before = $this->getProductRow($id);
        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::once())->method('send')->with(
            'quality@example.invalid',
            'Produktqualität prüfen',
            self::callback(static function (string $body) use ($id): bool {
                return str_contains($body, 'Produkt #' . $id)
                    && str_contains($body, 'Feld #4')
                    && str_contains($body, 'Fehlende Übersetzungen: en')
                    && str_contains($body, '1 Produkte geprüft, 2 Hinweise');
            })
        );
        QUI::$MailManager = $Mail;

        Crons::productQualityCheck(['email' => 'quality@example.invalid', 'languages' => 'de,en']);

        self::assertSame($before, $this->getProductRow($id));
    }

    public function testNoNotificationIsSentWhenAllRequestedTranslationsExist(): void
    {
        ProductTestHelper::createProduct('quality-complete');

        Crons::productQualityCheck(['email' => 'quality@example.invalid', 'languages' => 'de']);
    }

    public function testEmptyEmailRecipientUsesTheConfiguredAdministratorAddress(): void
    {
        $this->createProductWithMissingTranslations('quality-admin');
        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::once())->method('send')->with(
            'admin@example.invalid',
            self::anything(),
            self::anything()
        );
        QUI::$MailManager = $Mail;

        Crons::productQualityCheck(['languages' => 'en', 'email' => '  ']);
    }

    public function testBackendRecipientsCanBeSelectedWithoutAnEmailAddress(): void
    {
        $this->createProductWithMissingTranslations('quality-backend');
        QUI::$Conf->set('mail', 'admin_mail', '');
        $User = $this->configureBackendUsers(false);
        $Messages = $this->createMock(MessagesHandler::class);
        $Messages->expects(self::once())->method('sendAttention')->with(
            $User,
            self::callback(static fn (string $body): bool => str_contains($body, 'Fehlende Übersetzungen: en'))
        );
        QUI::$MessageHandler = $Messages;

        Crons::productQualityCheck([
            'notification' => 'backend',
            'users' => ', quality-user,quality-user, ',
            'languages' => 'en'
        ]);
    }

    public function testBothChannelsUseAdministratorDefaultsWhenRecipientsAreEmpty(): void
    {
        $this->createProductWithMissingTranslations('quality-both');
        $User = $this->configureBackendUsers(true);
        $Messages = $this->createMock(MessagesHandler::class);
        $Messages->expects(self::once())->method('sendAttention')->with($User, self::anything());
        QUI::$MessageHandler = $Messages;
        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::once())->method('send')->with(
            'admin@example.invalid',
            self::anything(),
            self::anything()
        );
        QUI::$MailManager = $Mail;

        Crons::productQualityCheck(['notification' => 'both', 'languages' => 'en']);
    }

    public function testBackendReportIsPersistedUntilTheRecipientReadsIt(): void
    {
        $this->createProductWithMissingTranslations('quality-persisted-message');
        $User = $this->configureBackendUsers(true);
        MessagesHandler::setup();
        QUI::$MessageHandler = new MessagesHandler();
        $Connection = QUI::getDataBaseConnection();
        $Connection->delete(MessagesHandler::table(), ['uid' => $User->getUUID()]);

        try {
            Crons::productQualityCheck(['notification' => 'backend', 'languages' => 'en']);

            $message = $Connection->fetchOne(
                'SELECT message FROM ' . QUI\Utils\Doctrine::quoteIdentifier(MessagesHandler::table())
                . ' WHERE uid = ?',
                [$User->getUUID()]
            );
            self::assertIsString($message);
            self::assertStringContainsString('Fehlende Übersetzungen: en', $message);
            $messages = QUI::getMessagesHandler()->getNewMessages($User);
            self::assertCount(1, $messages);
            self::assertSame($message, $messages[0]->getMessage());
            self::assertSame([], QUI::getMessagesHandler()->getNewMessages($User));
        } finally {
            $Connection->delete(MessagesHandler::table(), ['uid' => $User->getUUID()]);
        }
    }

    public function testDefaultLanguagesComeFromTheConfiguredProjects(): void
    {
        ProductTestHelper::createProduct('quality-default-languages');
        self::assertNotEmpty(ProductTestHelper::getProject()->getLanguages());

        Crons::productQualityCheck();
    }

    public function testReportEscapesDynamicFieldTitles(): void
    {
        $this->createProductWithMissingTranslations('quality-escaping');
        $attack = '"><script>alert(1)</script>';
        QUI::getLocale()->set('de', 'quiqqer/products', 'products.field.4.title', $attack);
        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::once())->method('send')->with(
            self::anything(),
            self::anything(),
            self::callback(static function (string $body): bool {
                return !str_contains($body, '<script>')
                    && str_contains($body, '&lt;script&gt;alert(1)&lt;/script&gt;');
            })
        );
        QUI::$MailManager = $Mail;

        Crons::productQualityCheck(['languages' => 'en']);
    }

    public function testPaginationChecksAllProductsAndLimitsReportDetails(): void
    {
        $Product = $this->createProductWithMissingTranslations('quality-pagination');
        $row = $this->getProductRow($Product->getId());
        unset($row['id']);

        for ($i = 0; $i < 100; $i++) {
            QUI::getDataBaseConnection()->insert(Tables::getProductTableName(), $row);
        }

        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::once())->method('send')->with(
            self::anything(),
            self::anything(),
            self::callback(static function (string $body): bool {
                return str_contains($body, '101 Produkte geprüft, 202 Hinweise')
                    && substr_count($body, 'Fehlende Übersetzungen:') === 100
                    && str_contains($body, 'ersten 100 Hinweise');
            })
        );
        QUI::$MailManager = $Mail;

        Crons::productQualityCheck(['languages' => 'en']);
    }

    public function testUnreadableProductsAreReportedAndDoNotStopOtherChecks(): void
    {
        $Broken = ProductTestHelper::createProduct('quality-broken');
        $Valid = $this->createProductWithMissingTranslations('quality-readable');
        QUI::getDataBaseConnection()->update(
            Tables::getProductTableName(),
            ['type' => 'QUITests\\MissingQualityProductType'],
            ['id' => $Broken->getId()]
        );
        $Mail = $this->createMock(MailManager::class);
        $Mail->expects(self::once())->method('send')->with(
            self::anything(),
            self::anything(),
            self::callback(static function (string $body) use ($Broken, $Valid): bool {
                return str_contains($body, 'Produkt #' . $Broken->getId() . ' konnte nicht geprüft werden')
                    && str_contains($body, 'Produkt #' . $Valid->getId())
                    && str_contains($body, '1 Produkte geprüft, 3 Hinweise');
            })
        );
        QUI::$MailManager = $Mail;

        Crons::productQualityCheck(['languages' => 'en']);
    }

    public function testMissingAdministratorEmailFailsClearly(): void
    {
        QUI::$Conf->set('mail', 'admin_mail', '');
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionMessage('Administrator-Adresse');

        Crons::productQualityCheck(['languages' => 'de']);
    }

    #[DataProvider('invalidParameters')]
    public function testInvalidSettingsAreRejectedBeforeSendingNotifications(array $params): void
    {
        $this->expectException(QUI\Exception::class);

        Crons::productQualityCheck($params);
    }

    public static function invalidParameters(): iterable
    {
        yield 'invalid email' => [['email' => 'invalid', 'languages' => 'de']];
        yield 'invalid mode' => [['notification' => 'invalid', 'languages' => 'de']];
        yield 'invalid language' => [['languages' => 'english']];
        yield 'invalid language type' => [['languages' => []]];
        yield 'invalid recipients type' => [['notification' => 'backend', 'users' => []]];
    }

    private function configureBackendUsers(bool $administrators): User
    {
        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn('phpunit-products-quality-user');
        $Users = $this->createMock(UsersManager::class);
        $Users->method('isSystemUser')->willReturn(false);
        $Users->method('isNobodyUser')->willReturn(false);

        if ($administrators) {
            $Users->expects(self::once())->method('getUsers')->with([
            'where' => [
                'su' => 1
            ]
            ])->willReturn([$User]);
        } else {
            $Users->expects(self::once())->method('get')->with('quality-user')->willReturn($User);
        }

        QUI::$Users = $Users;

        return $User;
    }

    private function createProductWithMissingTranslations(string $title): AbstractType
    {
        $Product = ProductTestHelper::createProduct($title);

        // The shared helper translates every project language, including English in CI.
        foreach ([Fields::FIELD_TITLE, Fields::FIELD_URL] as $fieldId) {
            $Field = $Product->getField($fieldId);
            $values = $Field->getValue();
            self::assertIsArray($values);
            $values['en'] = '';
            $Field->setValue($values);
        }

        ProductTestHelper::runAsSystemUser(static function () use ($Product): void {
            $Product->save();
        });

        $Reloaded = Products::getNewProductInstance($Product->getId());

        foreach ([Fields::FIELD_TITLE, Fields::FIELD_URL] as $fieldId) {
            $values = $Reloaded->getField($fieldId)->getValue();
            self::assertIsArray($values);
            self::assertSame('', $values['en']);
        }

        return $Reloaded;
    }

    /** @return array<string, mixed> */
    private function getProductRow(int $id): array
    {
        return QUI::getDataBaseConnection()->fetchAssociative(
            'SELECT * FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName()) . ' WHERE id = ?',
            [$id]
        );
    }
}
