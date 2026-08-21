<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\GroupList;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;
use QUI\Groups\Group;
use QUI\Groups\Manager as GroupManager;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;

class GroupListTest extends TestCase
{
    private ?UserManager $originalUsers;
    private ?GroupManager $originalGroups;

    protected function setUp(): void
    {
        $this->originalUsers = QUI::$Users;
        $this->originalGroups = QUI::$Groups;
    }

    protected function tearDown(): void
    {
        QUI::$Users = $this->originalUsers;
        QUI::$Groups = $this->originalGroups;
    }

    public function testCleanupAcceptsOnlyUsersFromConfiguredGroups(): void
    {
        $AllowedUser = $this->createUser('allowed-user', ['allowed-group']);
        $OutsideUser = $this->createUser('outside-user', ['outside-group']);
        $Users = $this->createMock(UserManager::class);
        $Users->method('get')->willReturnMap([
            ['allowed-user', $AllowedUser],
            ['outside-user', $OutsideUser]
        ]);
        QUI::$Users = $Users;

        $Field = new GroupList(9205, [
            'options' => [
                'groupIds' => ['allowed-group'],
                'multipleUsers' => true
            ]
        ]);

        self::assertSame(['allowed-user'], $Field->cleanup(['allowed-user']));
        self::assertSame([], $Field->cleanup(['outside-user']));
    }

    public function testValidationRejectsOutsideUsersAndExcessSelections(): void
    {
        $AllowedUser = $this->createUser('allowed-user', ['allowed-group']);
        $OutsideUser = $this->createUser('outside-user', ['outside-group']);
        $Users = $this->createMock(UserManager::class);
        $Users->method('get')->willReturnMap([
            ['allowed-user', $AllowedUser],
            ['outside-user', $OutsideUser]
        ]);
        QUI::$Users = $Users;

        $Field = new GroupList(9205, [
            'options' => [
                'groupIds' => ['allowed-group'],
                'multipleUsers' => true
            ]
        ]);
        $Field->validate(['allowed-user']);
        self::assertSame(['allowed-user'], $Field->cleanup(['allowed-user']));

        try {
            $Field->validate(['outside-user']);
            self::fail('A user outside the configured groups must be rejected.');
        } catch (Exception $Exception) {
            self::assertSame(
                'exception.field.unexptected.error',
                $Exception->getContext()['locale'][1]
            );
        }

        $SingleUserField = new GroupList(9206, [
            'options' => [
                'groupIds' => [],
                'multipleUsers' => false
            ]
        ]);
        self::assertSame([], $SingleUserField->cleanup(['allowed-user', 'outside-user']));

        $this->expectException(Exception::class);
        $SingleUserField->validate(['allowed-user', 'outside-user']);
    }

    public function testGroupsAndUsersAreResolvedFromConfiguredManagers(): void
    {
        $FirstUser = $this->createUser('first-user', ['allowed-group']);
        $SecondUser = $this->createUser('second-user', ['allowed-group']);
        $Users = $this->createMock(UserManager::class);
        $Users->method('get')->willReturnMap([
            [11, $FirstUser],
            [12, $SecondUser]
        ]);
        QUI::$Users = $Users;

        $Group = $this->createMock(Group::class);
        $Group->method('getUsers')->willReturn([
            ['id' => 11],
            ['id' => 12]
        ]);
        $Groups = $this->createMock(GroupManager::class);
        $Groups->method('get')->with('allowed-group')->willReturn($Group);
        QUI::$Groups = $Groups;

        $Field = new GroupList(9205, [
            'options' => ['groupIds' => ['allowed-group']]
        ]);

        self::assertSame([$Group], $Field->getGroups());
        self::assertSame([11, 12], $Field->getUserIds());
        self::assertSame([11 => $FirstUser, 12 => $SecondUser], $Field->getUsers());
    }

    public function testNumericGroupIdsAreStoredAsStableUuids(): void
    {
        $Group = $this->createMock(Group::class);
        $Group->method('getUUID')->willReturn('group-uuid');
        $Groups = $this->createMock(GroupManager::class);
        $Groups->method('get')->with(42)->willReturn($Group);
        QUI::$Groups = $Groups;

        $Field = new GroupList(9205, [
            'options' => ['groupIds' => [42]]
        ]);

        self::assertSame(['group-uuid'], $Field->getOption('groupIds'));
    }

    public function testSearchCacheAndLocalizedValueUseResolvedUserNames(): void
    {
        $FirstUser = $this->createUser('first-user', [], 'Alice', 'Alice', 'Tester');
        $SecondUser = $this->createUser('second-user', [], 'bob');
        $Users = $this->createMock(UserManager::class);
        $Users->method('get')->willReturnMap([
            ['first-user', $FirstUser],
            ['second-user', $SecondUser]
        ]);
        QUI::$Users = $Users;

        $SingleField = new GroupList(9205, [
            'value' => ['first-user'],
            'options' => ['groupIds' => []]
        ]);
        self::assertSame('Alice', $SingleField->getSearchCacheValue());
        self::assertSame('Alice Tester', $SingleField->getValueByLocale());

        $MultipleField = new GroupList(9205, [
            'value' => ['first-user', 'second-user'],
            'options' => ['groupIds' => []]
        ]);
        self::assertSame(',Alice,bob,', $MultipleField->getSearchCacheValue());
        self::assertSame(['Alice Tester', 'bob'], $MultipleField->getValueByLocale());

        $EmptyField = new GroupList(9205, [
            'value' => [],
            'options' => ['groupIds' => []]
        ]);
        self::assertNull($EmptyField->getSearchCacheValue());
    }

    public function testControlSearchAndViewMetadata(): void
    {
        $Field = new GroupList(9205, ['options' => ['groupIds' => []]]);

        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/GroupList',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/GroupListSettings',
            $Field->getJavaScriptSettings()
        );
        self::assertSame([
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE
        ], $Field->getSearchTypes());
        self::assertSame(Search::SEARCHTYPE_INPUTSELECTSINGLE, $Field->getDefaultSearchType());
        self::assertInstanceOf(View::class, $Field->getFrontendView());
        self::assertInstanceOf(View::class, $Field->getBackendView());
    }

    private function createUser(
        string $uuid,
        array $groups,
        ?string $name = null,
        ?string $firstname = null,
        ?string $lastname = null
    ): User&MockObject {
        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uuid);
        $User->method('getGroups')->with(false)->willReturn($groups);
        $User->method('getName')->willReturn($name ?? $uuid);
        $User->method('getUsername')->willReturn($name ?? $uuid);
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
