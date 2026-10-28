<?php

namespace QUITests\ERP\Products\Unit\Utils;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\ERP\Products\Product\Model;
use QUI\ERP\Products\Utils\ProductActivation;
use QUI\Projects\Media\Image;
use QUI\Projects\Project;

class ProductActivationTest extends TestCase
{
    public function testMissingImagePermissionsPreventProductAndImageActivation(): void
    {
        $Product = $this->createMock(Model::class);
        $First = $this->createInactiveImage(11);
        $Second = $this->createInactiveImage(12);
        $Product->method('getImages')->with(['active' => 0])->willReturn([$First, $Second]);
        $Product->method('getFieldValue')->willReturn(null);
        $Second->method('checkPermission')->willThrowException(new QUI\Permissions\Exception('denied'));
        $Product->expects(self::never())->method('activate');
        $First->expects(self::never())->method('activate');
        $Second->expects(self::never())->method('activate');

        $this->expectException(QUI\Permissions\Exception::class);
        ProductActivation::activate($Product, true);
    }

    public function testInvalidProductsDoNotActivateTheirImages(): void
    {
        $Product = $this->createMock(Model::class);
        $Image = $this->createInactiveImage(11);
        $Product->method('getImages')->willReturn([$Image]);
        $Product->method('getFieldValue')->willReturn(null);
        $Product->method('activate')->willThrowException(new QUI\Exception('invalid product'));
        $Image->expects(self::never())->method('activate');
        $Product->expects(self::never())->method('updateCache');

        $this->expectException(QUI\Exception::class);
        ProductActivation::activate($Product, true);
    }

    public function testDefaultActivationDoesNotInspectOrChangeImages(): void
    {
        $Product = $this->createMock(Model::class);
        $Product->expects(self::never())->method('getImages');
        $Product->expects(self::once())->method('activate');
        $Product->expects(self::never())->method('updateCache');

        ProductActivation::activate($Product);
    }

    private function createInactiveImage(int $id): Image
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn('test');
        $Image = $this->createMock(Image::class);
        $Image->method('getProject')->willReturn($Project);
        $Image->method('getId')->willReturn($id);
        $Image->method('isActive')->willReturn(false);
        $Image->method('isDeleted')->willReturn(false);

        return $Image;
    }
}
