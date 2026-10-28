<?php

namespace QUITests\ERP\Products\Integration\Controls;

use PHPUnit\Framework\Attributes\DataProvider;
use QUI;
use QUI\ERP\Products\Controls\ManufacturerList\ManufacturerList;
use QUI\ERP\Products\Field\Types\GroupList;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Manufacturers;
use QUI\Groups\Group;
use QUI\Groups\Manager as GroupManager;
use QUI\Users\Manager as UserManager;
use QUITests\ERP\Products\Integration\Product\ProductIntegrationTestCase;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionMethod;
use ReflectionProperty;

class ManufacturerListControlTest extends ProductIntegrationTestCase
{
    private ?UserManager $originalUsers;
    private ?GroupManager $originalGroups;
    private array $originalFieldList;
    private array $originalDeletedFieldIds;
    private array $originalManufacturerData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalUsers = QUI::$Users;
        $this->originalGroups = QUI::$Groups;
        $this->originalFieldList = (new ReflectionProperty(Fields::class, 'list'))->getValue();
        $this->originalDeletedFieldIds = (new ReflectionProperty(Fields::class, 'deletedFieldIds'))->getValue();
        $this->originalManufacturerData = (new ReflectionProperty(Manufacturers::class, 'manufacturerData'))->getValue();
        $this->installManufacturerFixtures();
    }

    protected function tearDown(): void
    {
        QUI::$Users = $this->originalUsers;
        QUI::$Groups = $this->originalGroups;

        foreach ([31, 32, 33] as $userId) {
            QUI::getDataBaseConnection()->delete(UserManager::table(), ['id' => $userId]);
        }

        (new ReflectionProperty(Fields::class, 'list'))->setValue(null, $this->originalFieldList);
        (new ReflectionProperty(Fields::class, 'deletedFieldIds'))->setValue(null, $this->originalDeletedFieldIds);
        (new ReflectionProperty(Manufacturers::class, 'manufacturerData'))->setValue(
            null,
            $this->originalManufacturerData
        );
        parent::tearDown();
    }

    public function testStartAndNextRenderSortedActiveManufacturersWithPagingState(): void
    {
        $rows = QUI\ERP\Products\Utils\Database::fetch([
            'select' => ['id'],
            'from' => UserManager::table(),
            'where' => [
                'id' => [
                    'type' => 'IN',
                    'value' => Manufacturers::getManufacturerUserIds(true)
                ]
            ],
            'order' => 'username ASC',
            'limit' => '0,2'
        ]);
        self::assertCount(2, $rows);

        $Control = new ManufacturerList([
            'Site' => ProductTestHelper::getCategorySite(),
            'productLoadNumber' => 2
        ]);

        $start = $Control->getStart();
        self::assertSame(3, $start['count']);
        self::assertTrue($start['more']);
        self::assertStringContainsString('Alice Maker', $start['html']);
        self::assertStringContainsString('Bea Builder', $start['html']);
        self::assertStringNotContainsString('fallback-maker', $start['html']);
        self::assertLessThan(
            strpos($start['html'], 'Bea Builder'),
            strpos($start['html'], 'Alice Maker')
        );

        $next = $Control->getNext(2, 3);
        self::assertSame(3, $next['count']);
        self::assertFalse($next['more']);
        self::assertStringContainsString('fallback-maker', $next['html']);
        self::assertStringNotContainsString('Alice Maker', $next['html']);
    }

    public function testBodyExposesManufacturerCountAndConfiguredAjaxAttributes(): void
    {
        $Control = new ManufacturerList([
            'Site' => ProductTestHelper::getCategorySite(),
            'autoload' => true,
            'autoloadAfter' => 4,
            'productLoadNumber' => 2
        ]);

        $html = $Control->getBody();

        self::assertStringContainsString('quiqqer-products-manufacturerList-entries', $html);
        self::assertStringContainsString('Alice Maker', $html);
        self::assertSame(ProductTestHelper::getProject()->getName(), $Control->getAttribute('data-project'));
        self::assertSame(ProductTestHelper::getProject()->getLang(), $Control->getAttribute('data-lang'));
        self::assertSame(ProductTestHelper::getCategorySite()->getId(), $Control->getAttribute('data-siteid'));
        self::assertSame(1, $Control->getAttribute('data-autoload'));
        self::assertSame(4, $Control->getAttribute('data-autoloadAfter'));
    }

    public function testPageSizeFollowsExplicitSettingAndViewDefaults(): void
    {
        $Method = new ReflectionMethod(ManufacturerList::class, 'getMax');

        self::assertSame(7, $Method->invoke(new ManufacturerList(['productLoadNumber' => 7])));
        self::assertSame(10, $Method->invoke(new ManufacturerList(['view' => 'list'])));
        self::assertSame(5, $Method->invoke(new ManufacturerList(['view' => 'detail'])));
        self::assertSame(9, $Method->invoke(new ManufacturerList()));
    }

    #[DataProvider('autoloadSettings')]
    public function testAutoloadPreservesManufacturerOverrides(
        int|string|false|null $localValue,
        int $globalValue,
        int|string $expected
    ): void {
        $Config = QUI::getPackage('quiqqer/products')->getConfig();
        self::assertNotNull($Config);
        $original = $Config->get('products', 'autoloadAfter');
        $Config->set('products', 'autoloadAfter', $globalValue);

        try {
            $Control = new ManufacturerList([
                'Site' => ProductTestHelper::getCategorySite(),
                'autoloadAfter' => $localValue
            ]);
            $Control->getBody();

            self::assertSame($expected, $Control->getAttribute('data-autoloadAfter'));
        } finally {
            if ($original === false) {
                $Config->del('products', 'autoloadAfter');
            } else {
                $Config->set('products', 'autoloadAfter', $original);
            }
        }
    }

    public static function autoloadSettings(): iterable
    {
        yield 'empty inherits global' => ['', 4, 4];
        yield 'false inherits global' => [false, 4, 4];
        yield 'null inherits global' => [null, 4, 4];
        yield 'integer zero disables autoload' => [0, 4, 0];
        yield 'string zero disables autoload' => ['0', 4, '0'];
        yield 'integer category threshold' => [2, 4, 2];
        yield 'string category threshold' => ['2', 4, '2'];
        yield 'inherit globally disabled autoload' => ['', 0, 0];
        yield 'category enables globally disabled autoload' => [2, 0, 2];
    }

    private function installManufacturerFixtures(): void
    {
        QUI::$Users = new UserManager();

        $Group = $this->createMock(Group::class);
        $Group->method('getUsers')->willReturn([
            ['id' => 31],
            ['id' => 32],
            ['id' => 33]
        ]);
        $Groups = $this->createMock(GroupManager::class);
        $Groups->method('get')->with('manufacturer-control-group')->willReturn($Group);
        QUI::$Groups = $Groups;

        Fields::setRuntimeField(new GroupList(Fields::FIELD_MANUFACTURER, [
            'options' => ['groupIds' => ['manufacturer-control-group']]
        ]));

        foreach (
            [
            [31, 'manufacturer-control-31', 'alpha-maker', 'Alice', 'Maker'],
            [32, 'manufacturer-control-32', 'beta-maker', 'Bea', 'Builder'],
            [33, 'manufacturer-control-33', 'fallback-maker', '', '']
            ] as [$id, $uuid, $username, $firstname, $lastname]
        ) {
            QUI::getDataBaseConnection()->insert(UserManager::table(), [
                'id' => $id,
                'uuid' => $uuid,
                'username' => $username,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'active' => 1
            ]);
        }
    }
}
