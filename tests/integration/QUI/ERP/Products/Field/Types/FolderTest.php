<?php

namespace QUITests\ERP\Products\Integration\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Folder;
use QUI\ERP\Products\Field\View;
use QUI\Projects\Project;
use QUITests\ERP\Products\Integration\ProjectTestHelper;
use ReflectionProperty;

class FolderTest extends TestCase
{
    private const EMPTY_FOLDER_ID = 9401;
    private const POPULATED_FOLDER_ID = 9402;
    private const FILE_ID = 9403;

    private static Project $Project;

    public static function setUpBeforeClass(): void
    {
        self::$Project = ProjectTestHelper::getProject();
        $Connection = QUI::getDataBaseConnection();
        $mediaTable = self::$Project->getMedia()->getTable();
        $relationTable = self::$Project->getMedia()->getTable('relations');
        $date = '2026-01-01 00:00:00';
        $userUuid = QUI::getUsers()->getSystemUser()->getUUID();

        foreach (
            [
                self::EMPTY_FOLDER_ID => 'phpunit-empty-folder',
                self::POPULATED_FOLDER_ID => 'phpunit-populated-folder'
            ] as $id => $name
        ) {
            $Connection->insert($mediaTable, [
                'id' => $id,
                'name' => $name,
                'title' => json_encode(['de' => $name]),
                'short' => json_encode(['de' => '']),
                'alt' => json_encode(['de' => '']),
                'type' => 'folder',
                'active' => 1,
                'deleted' => 0,
                'c_date' => $date,
                'e_date' => $date,
                'c_user' => $userUuid,
                'e_user' => $userUuid,
                'file' => '',
                'priority' => 0,
                'hidden' => 0,
                'pathHash' => md5($name)
            ]);
            $Connection->insert($relationTable, ['parent' => 1, 'child' => $id]);
        }

        $Connection->insert($mediaTable, [
            'id' => self::FILE_ID,
            'name' => 'phpunit-folder-document',
            'title' => json_encode(['de' => 'Dokument']),
            'short' => json_encode(['de' => '']),
            'alt' => json_encode(['de' => '']),
            'type' => 'file',
            'active' => 1,
            'deleted' => 0,
            'c_date' => $date,
            'e_date' => $date,
            'c_user' => $userUuid,
            'e_user' => $userUuid,
            'file' => '',
            'mime_type' => 'text/plain',
            'priority' => 0,
            'hidden' => 0,
            'pathHash' => md5('phpunit-folder-document')
        ]);
        $Connection->insert($relationTable, [
            'parent' => self::POPULATED_FOLDER_ID,
            'child' => self::FILE_ID
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $Media = self::$Project->getMedia();
        $mediaTable = $Media->getTable();
        $relationTable = $Media->getTable('relations');
        $ids = [self::EMPTY_FOLDER_ID, self::POPULATED_FOLDER_ID, self::FILE_ID];

        foreach ($ids as $id) {
            $Connection->delete($relationTable, ['child' => $id]);
        }

        foreach ([self::EMPTY_FOLDER_ID, self::POPULATED_FOLDER_ID] as $id) {
            $Connection->delete($relationTable, ['parent' => $id]);
        }

        foreach ($ids as $id) {
            $Connection->delete($mediaTable, ['id' => $id]);
        }

        $Children = new ReflectionProperty($Media, 'children');
        $children = $Children->getValue($Media);

        foreach ($ids as $id) {
            unset($children[$id]);
        }

        $Children->setValue($Media, $children);

        $UrlItemCache = new ReflectionProperty(QUI\Projects\Media\Utils::class, 'urlItemCache');
        $urlItemCache = $UrlItemCache->getValue();

        foreach ($ids as $id) {
            unset($urlItemCache[self::getMediaUrl($id)]);
        }

        $UrlItemCache->setValue(null, $urlItemCache);
    }

    public function testValidationAcceptsFoldersAndRejectsOtherMediaValues(): void
    {
        $Field = new Folder(9400, ['name' => 'downloads']);

        foreach (
            [
                [null, null],
                ['', null],
                [0, null],
                [self::getMediaUrl(self::EMPTY_FOLDER_ID), self::getMediaUrl(self::EMPTY_FOLDER_ID)]
            ] as [$value, $expected]
        ) {
            $Field->validate($value);
            self::assertSame($expected, $Field->cleanup($value));
        }

        foreach ([self::getMediaUrl(self::FILE_ID), 'not-a-media-url'] as $value) {
            try {
                $Field->validate($value);
                self::fail("Invalid folder value '$value' must be rejected.");
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

    public function testCleanupAndValueAccessUseCanonicalFolderUrl(): void
    {
        $Field = new Folder(9400, ['name' => 'downloads']);
        $folderUrl = self::getMediaUrl(self::EMPTY_FOLDER_ID);

        self::assertSame($folderUrl, $Field->cleanup($folderUrl));
        self::assertNull($Field->cleanup(self::getMediaUrl(self::FILE_ID)));
        self::assertNull($Field->cleanup('not-a-media-url'));
        self::assertNull($Field->cleanup(null));

        $Field->setValue($folderUrl);
        self::assertSame($folderUrl, $Field->getValue());
        self::assertSame(self::EMPTY_FOLDER_ID, $Field->getMediaFolder()?->getId());

        $Field->clearValue();
        self::assertNull($Field->getMediaFolder());
    }

    public function testEmptyStateReflectsActualFolderContents(): void
    {
        $Empty = new Folder(9400, [
            'name' => 'downloads',
            'value' => self::getMediaUrl(self::EMPTY_FOLDER_ID)
        ]);
        $Populated = new Folder(9400, [
            'name' => 'downloads',
            'value' => self::getMediaUrl(self::POPULATED_FOLDER_ID)
        ]);
        $Invalid = new Folder(9400, [
            'name' => 'downloads',
            'value' => 'not-a-media-url'
        ]);

        self::assertTrue($Empty->isEmpty());
        self::assertFalse($Populated->isEmpty());
        self::assertTrue($Invalid->isEmpty());
        self::assertNull($Invalid->getMediaFolder());
    }

    public function testViewsOptionsAndControlsExposeFolderBehavior(): void
    {
        $emptyFolderUrl = self::getMediaUrl(self::EMPTY_FOLDER_ID);
        $populatedFolderUrl = self::getMediaUrl(self::POPULATED_FOLDER_ID);
        $Field = new Folder(9400, [
            'name' => 'downloads',
            'public' => true,
            'value' => $emptyFolderUrl,
            'options' => ['mediaFolder' => $populatedFolderUrl]
        ]);

        self::assertSame($populatedFolderUrl, $Field->getValue());
        self::assertSame(self::POPULATED_FOLDER_ID, $Field->getMediaFolder()?->getId());
        self::assertTrue($Field->getOption('autoActivateItems'));
        self::assertFalse($Field->getOption('showFrontendTabIfEmpty'));
        self::assertSame('BIGINT(20)', $Field->getColumnType());
        self::assertFalse($Field->isSearchable());
        self::assertSame([], $Field->getSearchTypes());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Folder',
            $Field->getJavaScriptControl()
        );
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/FolderSettings',
            $Field->getJavaScriptSettings()
        );

        $BackendView = $Field->getBackendView();
        $FrontendView = $Field->getFrontendView();

        self::assertInstanceOf(View::class, $BackendView);
        self::assertInstanceOf(View::class, $FrontendView);
        self::assertSame($populatedFolderUrl, $BackendView->getValue());
        self::assertSame($populatedFolderUrl, $FrontendView->getValue());
        self::assertStringContainsString(htmlspecialchars($populatedFolderUrl), $FrontendView->create());
    }

    private static function getMediaUrl(int $id): string
    {
        return 'image.php?id=' . $id . '&project=' . self::$Project->getName();
    }
}
