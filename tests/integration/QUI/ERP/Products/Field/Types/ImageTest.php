<?php

namespace QUITests\ERP\Products\Integration\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\Types\Image;
use QUI\ERP\Products\Field\Types\ImageFrontendView;
use QUI\ERP\Products\Field\View;
use QUI\Projects\Project;
use QUITests\ERP\Products\Integration\ProjectTestHelper;
use ReflectionProperty;

class ImageTest extends TestCase
{
    private const IMAGE_ID = 9301;
    private const FILE_ID = 9302;

    private static Project $Project;

    public static function setUpBeforeClass(): void
    {
        self::$Project = ProjectTestHelper::getProject();
        $Connection = QUI::getDataBaseConnection();
        $mediaTable = self::$Project->getMedia()->getTable();
        $relationTable = self::$Project->getMedia()->getTable('relations');
        $date = '2026-01-01 00:00:00';
        $userUuid = QUI::getUsers()->getSystemUser()->getUUID();

        $Connection->insert($mediaTable, [
            'id' => self::IMAGE_ID,
            'name' => 'phpunit-image',
            'title' => json_encode(['de' => 'Produktbild']),
            'short' => json_encode(['de' => '']),
            'alt' => json_encode(['de' => 'Produktbild']),
            'type' => 'image',
            'active' => 1,
            'deleted' => 0,
            'c_date' => $date,
            'e_date' => $date,
            'c_user' => $userUuid,
            'e_user' => $userUuid,
            'file' => 'phpunit-image.svg',
            'mime_type' => 'image/svg+xml',
            'image_height' => 40,
            'image_width' => 80,
            'priority' => 0,
            'hidden' => 0,
            'pathHash' => md5('phpunit-image.svg')
        ]);
        $Connection->insert($relationTable, ['parent' => 1, 'child' => self::IMAGE_ID]);

        $Connection->insert($mediaTable, [
            'id' => self::FILE_ID,
            'name' => 'phpunit-document',
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
            'file' => 'phpunit-document.txt',
            'mime_type' => 'text/plain',
            'priority' => 0,
            'hidden' => 0,
            'pathHash' => md5('phpunit-document.txt')
        ]);
        $Connection->insert($relationTable, ['parent' => 1, 'child' => self::FILE_ID]);
    }

    public static function tearDownAfterClass(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $mediaTable = self::$Project->getMedia()->getTable();
        $relationTable = self::$Project->getMedia()->getTable('relations');

        foreach ([self::IMAGE_ID, self::FILE_ID] as $id) {
            $Connection->delete($relationTable, ['child' => $id]);
            $Connection->delete($mediaTable, ['id' => $id]);
        }

        $Cache = new ReflectionProperty(QUI\Projects\Media\Utils::class, 'urlItemCache');
        $cache = $Cache->getValue();
        unset($cache[self::getMediaUrl(self::IMAGE_ID)], $cache[self::getMediaUrl(self::FILE_ID)]);
        $Cache->setValue(null, $cache);
    }

    public function testValidationAcceptsImagesAndRejectsFilesAndMalformedUrls(): void
    {
        $Field = new Image(9300, ['name' => 'product-image']);

        foreach (
            [
                [null, null],
                ['', null],
                [self::getMediaUrl(self::IMAGE_ID), self::getMediaUrl(self::IMAGE_ID)]
            ] as [$value, $expected]
        ) {
            $Field->validate($value);
            self::assertSame($expected, $Field->cleanup($value));
        }

        foreach ([self::getMediaUrl(self::FILE_ID), 'not-a-media-url'] as $value) {
            try {
                $Field->validate($value);
                self::fail("Invalid image value '$value' must be rejected.");
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

    public function testCleanupReturnsCanonicalImageUrlAndRejectsOtherValues(): void
    {
        $Field = new Image(9300, ['name' => 'product-image']);
        $imageUrl = self::getMediaUrl(self::IMAGE_ID);

        self::assertSame($imageUrl, $Field->cleanup($imageUrl));
        self::assertNull($Field->cleanup(self::getMediaUrl(self::FILE_ID)));
        self::assertNull($Field->cleanup('not-a-media-url'));
        self::assertNull($Field->cleanup(null));
    }

    public function testViewsAndControlExposeImageFieldBehavior(): void
    {
        $Field = new Image(9300, [
            'name' => 'product-image',
            'public' => true,
            'value' => self::getMediaUrl(self::IMAGE_ID)
        ]);

        self::assertInstanceOf(View::class, $Field->getBackendView());
        self::assertSame(
            'package/quiqqer/products/bin/controls/fields/types/Image',
            $Field->getJavaScriptControl()
        );

        $FrontendView = $Field->getFrontendView();
        self::assertInstanceOf(ImageFrontendView::class, $FrontendView);

        $html = $FrontendView->create();
        self::assertStringContainsString('quiqqer-product-field-value', $html);
        self::assertStringContainsString('data-zoom="1"', $html);
        self::assertStringContainsString('phpunit-image.svg', $html);
        self::assertStringContainsString('Produktbild', $html);

        $InvalidField = new Image(9300, [
            'name' => 'product-image',
            'public' => true,
            'value' => 'not-a-media-url'
        ]);
        $invalidHtml = $InvalidField->getFrontendView()->create();

        self::assertStringContainsString('quiqqer-product-field-value', $invalidHtml);
        self::assertStringNotContainsString('<a href=', $invalidHtml);
    }

    private static function getMediaUrl(int $id): string
    {
        return 'image.php?id=' . $id . '&project=' . self::$Project->getName();
    }
}
