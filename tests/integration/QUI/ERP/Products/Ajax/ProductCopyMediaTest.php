<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Model;
use QUI\ERP\Products\Product\Types\VariantChild;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\ProductMediaCopy;
use QUI\ERP\Products\Utils\Tables;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Image;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class ProductCopyMediaTest extends AjaxTestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    /** @var array<string, mixed> */
    private array $urlCache = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        ProductTestHelper::installCompleteFieldFixtures();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->urlCache = (new ReflectionProperty(QUI\Projects\Media\Utils::class, 'urlItemCache'))->getValue();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        (new ReflectionProperty(QUI\Projects\Media\Utils::class, 'urlItemCache'))->setValue(null, $this->urlCache);
    }

    public function testCopyModeCreatesIndependentImageFilesAndRemapsTheMainImage(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-images');
        $Folder = $Source->getMediaFolder();
        $Image = $this->createImage($Folder, true);
        $Inactive = $this->createImage($Folder, false);
        $this->setMainImage($Source, $Image);
        $Nested = ProductTestHelper::runAsSystemUser(static fn () => $Folder->createFolder('nested'));
        $this->createImage($Nested, true);
        $textFile = $this->temporaryFile('.txt', 'Product document');
        ProductTestHelper::runAsSystemUser(static fn () => $Folder->uploadFile($textFile));

        $Copy = $this->copy($Source, ProductMediaCopy::COPY);
        $CopyFolder = $Copy->getMediaFolder();
        self::assertNotSame($Folder->getId(), $CopyFolder->getId());
        self::assertCount(1, $Copy->getImages());
        self::assertCount(1, $Copy->getImages(['active' => 0]));
        self::assertSame([], $Copy->getFiles());
        self::assertNotSame($Image->getId(), $Copy->getImage()->getId());
        self::assertNotSame($Image->getFullPath(), $Copy->getImage()->getFullPath());
        self::assertSame(file_get_contents($Image->getFullPath()), file_get_contents($Copy->getImage()->getFullPath()));
        self::assertSame(7, (int)$Copy->getImage()->getAttribute('priority'));
        self::assertTrue($Copy->getImage()->isActive());
        self::assertFalse($Copy->getImages(['active' => 0])[0]->isActive());
        self::assertTrue($Inactive->getParent()->getId() === $Folder->getId());
        self::assertCount(1, $CopyFolder->getChildByName('nested')->getImages());
        self::assertSame($Image->getUrl(), $Source->getFieldValue(Fields::FIELD_IMAGE));
    }

    public function testEmptyModeUsesItsOwnEmptyFolderAndClearsTheMainImage(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-empty');
        $Image = $this->createImage($Source->getMediaFolder(), true);
        $this->setMainImage($Source, $Image);
        $Copy = $this->copy($Source, ProductMediaCopy::EMPTY);

        self::assertNotSame($Source->getMediaFolder()->getId(), $Copy->getMediaFolder()->getId());
        self::assertSame([], $Copy->getImages());
        self::assertEmpty($Copy->getFieldValue(Fields::FIELD_IMAGE));
        self::assertFileExists($Image->getFullPath());
        self::assertSame($Image->getUrl(), $Source->getFieldValue(Fields::FIELD_IMAGE));
    }

    public function testDeletingTheOriginalPreservesSharedMediaUntilTheLastProductIsDeleted(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-share');
        $Folder = $Source->getMediaFolder();
        $Image = $this->createImage($Folder, true);
        $this->setMainImage($Source, $Image);
        $Copy = $this->copy($Source, ProductMediaCopy::SHARE);
        self::assertSame($Folder->getId(), $Copy->getMediaFolder()->getId());
        self::assertSame($Image->getId(), $Copy->getImage()->getId());

        ProductTestHelper::runAsSystemUser(static fn () => $Source->delete());
        self::assertTrue(ProductMediaCopy::isFolderShared($Folder, 0));
        self::assertFileExists($Image->getFullPath());
        self::assertSame($Folder->getId(), $Copy->getMediaFolder()->getId());

        ProductTestHelper::runAsSystemUser(static fn () => $Copy->delete());
        self::assertFalse(ProductMediaCopy::isFolderShared($Folder, 0));
        $table = QUI\Utils\Doctrine::quoteIdentifier($Folder->getMedia()->getTable());
        self::assertSame(0, (int)QUI::getDataBaseConnection()->fetchOne(
            'SELECT COUNT(*) FROM ' . $table . ' WHERE id = ?',
            [$Folder->getId()]
        ));
    }

    public function testDeletingTheSharedCopyFirstPreservesTheOriginalImages(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-share-reverse');
        $Image = $this->createImage($Source->getMediaFolder(), true);
        $Copy = $this->copy($Source, ProductMediaCopy::SHARE);
        ProductTestHelper::runAsSystemUser(static fn () => $Copy->delete());
        self::assertFileExists($Image->getFullPath());
        self::assertCount(1, $Source->getImages());
    }

    public function testSeparateCopiesSurviveDeletionOfTheOriginal(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-independent');
        $this->createImage($Source->getMediaFolder(), true);
        $Copy = $this->copy($Source, ProductMediaCopy::COPY);
        $CopyImage = $Copy->getImages()[0];
        ProductTestHelper::runAsSystemUser(static fn () => $Source->delete());
        self::assertFileExists($CopyImage->getFullPath());
        self::assertCount(1, $Copy->getImages());
    }

    public function testAnExternalMainImageIsCopiedAndProtectsItsOriginalFolder(): void
    {
        $Owner = ProductTestHelper::createProduct('copy-media-external-owner');
        $Source = ProductTestHelper::createProduct('copy-media-external-main');
        $Image = $this->createImage($Owner->getMediaFolder(), true);
        $this->setMainImage($Source, $Image);
        $Copy = $this->copy($Source, ProductMediaCopy::COPY);
        self::assertNotSame($Image->getId(), $Copy->getImage()->getId());
        self::assertSame($Copy->getMediaFolder()->getId(), $Copy->getImage()->getParent()->getId());
        ProductTestHelper::runAsSystemUser(static fn () => $Owner->delete());
        self::assertFileExists($Image->getFullPath());
    }

    public function testReferencesToDescendantFoldersPreventTheirAncestorFromBeingDeleted(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-ancestor');
        $Other = ProductTestHelper::createProduct('copy-media-descendant');
        $Nested = ProductTestHelper::runAsSystemUser(static fn () => $Source->getMediaFolder()->createFolder('nested'));
        $Image = $this->createImage($Nested, true);
        $Other->getField(Fields::FIELD_FOLDER)->setValue($Nested->getUrl());
        ProductTestHelper::runAsSystemUser(static fn ($User) => $Other->save($User));
        ProductTestHelper::runAsSystemUser(static fn () => $Source->delete());
        self::assertFileExists($Image->getFullPath());
        self::assertCount(1, $Other->getImages());
    }

    public function testVariantCopyGetsAnIndependentMediaFolder(): void
    {
        $Parent = ProductTestHelper::createProduct('copy-media-parent', 10, VariantParent::class);
        self::assertInstanceOf(VariantParent::class, $Parent);
        $Child = $Parent->createVariant();
        $this->createImage($Parent->getMediaFolder(), true);
        $Copy = $this->copy($Child, ProductMediaCopy::COPY);
        self::assertInstanceOf(VariantChild::class, $Copy);
        self::assertTrue($Copy->hasOwnMediaFolder());
        self::assertNotSame($Parent->getMediaFolder()->getId(), $Copy->getMediaFolder()->getId());
        self::assertCount(1, $Copy->getImages());
    }

    public function testInvalidModesAreRejectedBeforeCreatingACopy(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-invalid');
        $before = $this->productCount();

        try {
            $this->copy($Source, 'invalid');
            self::fail('Invalid modes must be rejected.');
        } catch (QUI\Exception) {
            self::assertSame($before, $this->productCount());
        }
    }

    public function testFailedImageCopyRemovesTheIncompleteProduct(): void
    {
        $Source = ProductTestHelper::createProduct('copy-media-failed');
        $Image = $this->createImage($Source->getMediaFolder(), true);
        unlink($Image->getFullPath());
        $before = $this->productCount();

        try {
            $this->copy($Source, ProductMediaCopy::COPY);
            self::fail('Copying a missing image file must fail.');
        } catch (QUI\Exception) {
            self::assertSame($before, $this->productCount());
            self::assertTrue(Products::existsProduct($Source->getId()));
        }
    }

    private function copy(Model $Source, string $mode): Model
    {
        $id = ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
            'products/copy.php',
            'package_quiqqer_products_ajax_products_copy',
            $Source->getId(),
            $mode
        ));

        return Products::getNewProductInstance($id);
    }

    private function createImage(Folder $Folder, bool $active): Image
    {
        $file = $this->temporaryFile(
            '.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="40"><rect width="80" height="40" fill="#369"/></svg>'
        );
        $Image = ProductTestHelper::runAsSystemUser(static fn () => $Folder->uploadFile($file));
        self::assertInstanceOf(Image::class, $Image);
        ProductTestHelper::runAsSystemUser(static function () use ($Image, $active): void {
            $Image->setAttribute('priority', 7);
            $Image->save();

            if ($active) {
                $Image->activate();
            } else {
                $Image->deactivate();
            }
        });

        return $Image;
    }

    private function setMainImage(Model $Product, Image $Image): void
    {
        $Product->getField(Fields::FIELD_IMAGE)->setValue($Image->getUrl());
        ProductTestHelper::runAsSystemUser(static fn ($User) => $Product->save($User));
    }

    private function temporaryFile(string $extension, string $contents): string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'product-copy-');
        $file = $temporary . $extension;
        rename($temporary, $file);
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }

    private function productCount(): int
    {
        $table = QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName());

        return (int)QUI::getDataBaseConnection()->fetchOne('SELECT COUNT(*) FROM ' . $table);
    }
}
