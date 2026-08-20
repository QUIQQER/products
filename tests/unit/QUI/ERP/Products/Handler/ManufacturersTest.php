<?php

namespace QUITests\ERP\Products\Unit\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Types\GroupList;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Manufacturers;
use QUI\Groups\Group;
use QUI\Groups\Manager as GroupManager;
use QUI\Projects\Manager as ProjectManager;
use QUI\Projects\Media;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Users\Address;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;

class ManufacturersTest extends TestCase
{
    private ?UserManager $originalUsers;
    private ?GroupManager $originalGroups;
    private ?ProjectManager $originalProjectManager;
    private ?Project $originalStandardProject;
    private array $originalFieldList;
    private array $originalDeletedFieldIds;
    private array $originalManufacturerData;

    protected function setUp(): void
    {
        $this->originalUsers = QUI::$Users;
        $this->originalGroups = QUI::$Groups;
        $this->originalProjectManager = QUI::$ProjectManager;
        $this->originalStandardProject = ProjectManager::$Standard;
        $this->originalFieldList = (new ReflectionProperty(Fields::class, 'list'))->getValue();
        $this->originalDeletedFieldIds = (new ReflectionProperty(Fields::class, 'deletedFieldIds'))->getValue();
        $this->originalManufacturerData = (new ReflectionProperty(Manufacturers::class, 'manufacturerData'))->getValue();
    }

    protected function tearDown(): void
    {
        QUI::$Users = $this->originalUsers;
        QUI::$Groups = $this->originalGroups;
        QUI::$ProjectManager = $this->originalProjectManager;
        ProjectManager::$Standard = $this->originalStandardProject;
        foreach ([21, 22, 23] as $userId) {
            QUI::getDataBaseConnection()->delete(UserManager::table(), ['id' => $userId]);
        }
        (new ReflectionProperty(Fields::class, 'list'))->setValue(null, $this->originalFieldList);
        (new ReflectionProperty(Fields::class, 'deletedFieldIds'))->setValue(null, $this->originalDeletedFieldIds);
        (new ReflectionProperty(Manufacturers::class, 'manufacturerData'))->setValue(
            null,
            $this->originalManufacturerData
        );
    }

    public function testManufacturerIdsUsersAndMembershipComeFromConfiguredGroup(): void
    {
        [$FirstUser, $SecondUser, $ThirdUser] = $this->installManufacturerFixtures();

        self::assertSame([21, 22, 23], Manufacturers::getManufacturerUserIds());
        self::assertSame([21, 23], array_map('intval', Manufacturers::getManufacturerUserIds(true)));
        self::assertSame([$SecondUser], Manufacturers::getManufacturerUsers(1, 1));
        self::assertSame([$FirstUser, $SecondUser, $ThirdUser], Manufacturers::getManufacturerUsers());
        self::assertTrue(Manufacturers::isManufacturer(21));
        self::assertFalse(Manufacturers::isManufacturer(99));
    }

    public function testManufacturerTitlePrefersCompanyThenFullNameThenUsername(): void
    {
        $this->installManufacturerFixtures();

        self::assertSame('Acme Manufacturing', Manufacturers::getManufacturerTitle(21));
        self::assertSame('Bea Builder', Manufacturers::getManufacturerTitle(22));
        self::assertSame('fallback-maker', Manufacturers::getManufacturerTitle(23));
        self::assertSame('', Manufacturers::getManufacturerTitle(null));
    }

    public function testManufacturerUrlAndMissingImageUsePersistedManufacturerData(): void
    {
        $this->installManufacturerFixtures();
        QUI::$Users = new UserManager();
        $Site = $this->createMock(Site::class);
        $Site->method('getUrlRewrittenWithHost')->willReturn('https://shop.example/manufacturers');
        $Project = $this->createMock(Project::class);
        $Project->method('getSites')->willReturn([$Site]);

        self::assertSame(
            'https://shop.example/manufacturers/company-maker',
            Manufacturers::getManufacturerUrl(21, $Project)
        );

        $Media = $this->createMock(Media::class);
        $Media->method('getPlaceholderImage')->willReturn(null);
        $StandardProject = $this->createMock(Project::class);
        $StandardProject->method('getMedia')->willReturn($Media);
        ProjectManager::$Standard = $StandardProject;
        QUI::$ProjectManager = new ProjectManager();
        self::assertNull(Manufacturers::getManufacturerImage(21));
    }

    /**
     * @return array{0: User&MockObject, 1: User&MockObject, 2: User&MockObject}
     */
    private function installManufacturerFixtures(): array
    {
        $CompanyAddress = $this->createMock(Address::class);
        $CompanyAddress->method('getAttribute')->with('company')->willReturn('Acme Manufacturing');

        $FirstUser = $this->createUser(21, 'company-maker', null, null);
        $FirstUser->method('getStandardAddress')->willReturn($CompanyAddress);
        $SecondUser = $this->createUser(22, 'named-maker', 'Bea', 'Builder');
        $SecondUser->method('getStandardAddress')->willThrowException(new QUI\Exception('No address'));
        $ThirdUser = $this->createUser(23, 'fallback-maker', null, null);
        $ThirdUser->method('getStandardAddress')->willThrowException(new QUI\Exception('No address'));

        $Users = $this->createMock(UserManager::class);
        $Users->method('get')->willReturnMap([
            [21, $FirstUser],
            [22, $SecondUser],
            [23, $ThirdUser]
        ]);
        QUI::$Users = $Users;

        $Group = $this->createMock(Group::class);
        $Group->method('getUsers')->willReturn([
            ['id' => 21],
            ['id' => 22],
            ['id' => 23]
        ]);
        $Groups = $this->createMock(GroupManager::class);
        $Groups->method('get')->with('manufacturer-group')->willReturn($Group);
        QUI::$Groups = $Groups;

        Fields::setRuntimeField(new GroupList(Fields::FIELD_MANUFACTURER, [
            'options' => ['groupIds' => ['manufacturer-group']]
        ]));

        $Connection = QUI::getDataBaseConnection();
        foreach (
            [
            [21, 'manufacturer-21', 'company-maker', 1],
            [22, 'manufacturer-22', 'named-maker', 0],
            [23, 'manufacturer-23', 'fallback-maker', 1]
            ] as [$id, $uuid, $username, $active]
        ) {
            $Connection->insert(UserManager::table(), [
                'id' => $id,
                'uuid' => $uuid,
                'username' => $username,
                'active' => $active
            ]);
        }

        return [$FirstUser, $SecondUser, $ThirdUser];
    }

    private function createUser(
        int $id,
        string $username,
        ?string $firstname,
        ?string $lastname
    ): User&MockObject {
        $User = $this->createMock(User::class);
        $User->method('getId')->willReturn($id);
        $User->method('getUUID')->willReturn('manufacturer-' . $id);
        $User->method('getUsername')->willReturn($username);
        $User->method('getAttribute')->willReturnCallback(
            static fn (string $key): mixed => match ($key) {
                'firstname' => $firstname,
                'lastname' => $lastname,
                default => null
            }
        );

        return $User;
    }
}
