<?php

namespace QUITests\ERP\Products\Integration\Ajax;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Types\VariantParent;
use QUI\ERP\Products\Utils\ProductActivation;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Image;
use QUITests\ERP\Products\Integration\Product\ProductTestHelper;
use ReflectionProperty;

class ProductActivationAjaxTest extends AjaxTestCase
{
    /** @var list<int> */
    private array $mediaIds = [];

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
        $Media = ProductTestHelper::getProject()->getMedia();
        $Connection = QUI::getDataBaseConnection();

        foreach ($this->mediaIds as $id) {
            $Connection->delete($Media->getTable('relations'), ['child' => $id]);
            $Connection->delete($Media->getTable(), ['id' => $id]);
        }

        // The URL resolver caches image objects for the lifetime of the PHP process.
        (new ReflectionProperty(QUI\Projects\Media\Utils::class, 'urlItemCache'))->setValue(null, $this->urlCache);
        parent::tearDown();
    }

    public function testInspectionCountsInactiveImagesWithoutChangingProductsOrMedia(): void
    {
        $Product = ProductTestHelper::createProduct('activation-inspection');
        $Folder = ProductTestHelper::runAsSystemUser(static fn () => $Product->createMediaFolder());
        $Inactive = $this->createMediaItem($Folder, false);
        $Active = $this->createMediaItem($Folder, true);
        $Document = $this->createMediaItem($Folder, false, 'file');
        $Deleted = $this->createMediaItem($Folder, false);
        QUI::getDataBaseConnection()->update(
            ProductTestHelper::getProject()->getMedia()->getTable(),
            ['deleted' => 1],
            ['id' => $Deleted->getId()]
        );

        $count = ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
            'products/getInactiveImageCount.php',
            'package_quiqqer_products_ajax_products_getInactiveImageCount',
            json_encode([$Product->getId(), $Product->getId()], JSON_THROW_ON_ERROR)
        ));

        self::assertSame(1, $count);
        self::assertFalse($Product->isActive());
        self::assertFalse($Inactive->isActive());
        self::assertTrue($Active->isActive());
        self::assertFalse($Document->isActive());
    }

    public function testDefaultAndExplicitOptOutLeaveExistingImagesInactive(): void
    {
        foreach ([null, 0] as $activateImages) {
            $Product = ProductTestHelper::createProduct('activation-without-images');
            $Folder = ProductTestHelper::runAsSystemUser(static fn () => $Product->createMediaFolder());
            $Image = $this->createMediaItem($Folder, false);
            $arguments = [$Product->getId()];

            if ($activateImages !== null) {
                $arguments[] = $activateImages;
            }

            ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
                'products/activate.php',
                'package_quiqqer_products_ajax_products_activate',
                ...$arguments
            ));

            self::assertTrue(Products::getNewProductInstance($Product->getId())->isActive());
            self::assertSame(0, $this->readMediaActive($Image->getId()));
        }
    }

    public function testOptInActivatesImagesForMultipleProductsButLeavesDocumentsInactive(): void
    {
        $productIds = [];
        $imageIds = [];
        $documentIds = [];

        foreach (['first', 'second'] as $name) {
            $Product = ProductTestHelper::createProduct('activation-bulk-' . $name);
            $Folder = ProductTestHelper::runAsSystemUser(static fn () => $Product->createMediaFolder());
            $productIds[] = $Product->getId();
            $imageIds[] = $this->createMediaItem($Folder, false)->getId();
            $documentIds[] = $this->createMediaItem($Folder, false, 'file')->getId();
        }

        ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
            'products/activate.php',
            'package_quiqqer_products_ajax_products_activate',
            json_encode($productIds, JSON_THROW_ON_ERROR),
            '1'
        ));

        foreach ($productIds as $id) {
            self::assertTrue(Products::getNewProductInstance($id)->isActive());
        }

        foreach ($imageIds as $id) {
            self::assertSame(1, $this->readMediaActive($id));
        }

        foreach ($documentIds as $id) {
            self::assertSame(0, $this->readMediaActive($id));
        }
    }

    public function testExplicitMainImageOutsideTheFolderIsIncludedAndNotCountedTwice(): void
    {
        $Product = ProductTestHelper::createProduct('activation-main-image');
        $Other = ProductTestHelper::createProduct('activation-image-owner');
        $Folder = ProductTestHelper::runAsSystemUser(static fn () => $Other->createMediaFolder());
        $Image = $this->createMediaItem($Folder, false);
        self::assertInstanceOf(Image::class, $Image);
        $Product->getField(Fields::FIELD_IMAGE)->setValue($Image->getUrl());
        ProductTestHelper::runAsSystemUser(static fn ($User) => $Product->save($User));

        $count = ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
            'products/getInactiveImageCount.php',
            'package_quiqqer_products_ajax_products_getInactiveImageCount',
            json_encode([$Product->getId(), $Other->getId()], JSON_THROW_ON_ERROR)
        ));
        self::assertSame(1, $count);

        ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
            'products/activate.php',
            'package_quiqqer_products_ajax_products_activate',
            $Product->getId(),
            1
        ));
        self::assertSame(1, $this->readMediaActive($Image->getId()));
        self::assertFalse($Other->isActive());
    }

    public function testVariantActivationSupportsTheSameImageOptIn(): void
    {
        $Parent = ProductTestHelper::createProduct('activation-variant-parent', 12, VariantParent::class);
        self::assertInstanceOf(VariantParent::class, $Parent);
        $Child = $Parent->createVariant();
        $Folder = ProductTestHelper::runAsSystemUser(static fn () => $Child->createOwnMediaFolder());
        $Image = $this->createMediaItem($Folder, false);

        ProductTestHelper::runAsSystemUser(fn () => $this->invokeEndpoint(
            'products/variant/generate/activate.php',
            'package_quiqqer_products_ajax_products_variant_generate_activate',
            json_encode([$Child->getId()], JSON_THROW_ON_ERROR),
            1
        ));

        self::assertTrue(Products::getNewProductInstance($Child->getId())->isActive());
        self::assertSame(1, $this->readMediaActive($Image->getId()));
    }

    public function testProductsWithoutImagesNeedNoConfirmation(): void
    {
        $Product = ProductTestHelper::createProduct('activation-no-images');
        self::assertSame([], ProductActivation::getInactiveImages($Product));
    }

    private function createMediaItem(
        Folder $Folder,
        bool $active,
        string $type = 'image'
    ): QUI\Projects\Media\Item {
        $Media = ProductTestHelper::getProject()->getMedia();
        $Connection = QUI::getDataBaseConnection();
        $name = 'phpunit-activation-' . bin2hex(random_bytes(8));
        $date = date('Y-m-d H:i:s');
        $uuid = QUI::getUsers()->getSystemUser()->getUUID();
        $Connection->insert($Media->getTable(), [
            'name' => $name,
            'title' => json_encode(['de' => $name]),
            'short' => json_encode(['de' => '']),
            'alt' => json_encode(['de' => $name]),
            'type' => $type,
            'active' => (int)$active,
            'deleted' => 0,
            'c_date' => $date,
            'e_date' => $date,
            'c_user' => $uuid,
            'e_user' => $uuid,
            'file' => $name . '.svg',
            'mime_type' => 'image/svg+xml',
            'image_height' => 40,
            'image_width' => 80,
            'priority' => 0,
            'hidden' => 0,
            'pathHash' => md5($name . '.svg')
        ]);
        $table = QUI\Utils\Doctrine::quoteIdentifier($Media->getTable());
        $id = (int)$Connection->fetchOne('SELECT id FROM ' . $table . ' WHERE name = ?', [$name]);
        $this->mediaIds[] = $id;
        $Connection->insert($Media->getTable('relations'), ['parent' => $Folder->getId(), 'child' => $id]);

        return $Media->get($id);
    }

    private function readMediaActive(int $id): int
    {
        $table = QUI\Utils\Doctrine::quoteIdentifier(ProductTestHelper::getProject()->getMedia()->getTable());

        return (int)QUI::getDataBaseConnection()->fetchOne('SELECT active FROM ' . $table . ' WHERE id = ?', [$id]);
    }
}
