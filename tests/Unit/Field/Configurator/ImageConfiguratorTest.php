<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ImageConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTestCase;

class ImageConfiguratorTest extends AbstractFieldTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new ImageConfigurator('/project');
    }

    public function testRelativeUploadDirIsResolvedFromProjectDir(): void
    {
        $field = ImageField::new('photo')->setUploadDir('public/uploads/images');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('/project/public/uploads/images/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testAbsoluteUploadDirIsKeptAsIs(): void
    {
        // absolute paths must not be re-rooted under the project dir. See issue #7459.
        $field = ImageField::new('photo')->setUploadDir('/mnt/data/images');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('/mnt/data/images/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testStreamWrapperUploadDirIsKeptAsIs(): void
    {
        $field = ImageField::new('photo')->setUploadDir('s3://bucket/images');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('s3://bucket/images/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testMissingUploadDirThrows(): void
    {
        $field = ImageField::new('photo');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('setUploadDir()');
        $this->configure($field, Crud::PAGE_EDIT);
    }
}
