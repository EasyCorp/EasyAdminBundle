<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\FileConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTestCase;

class FileConfiguratorTest extends AbstractFieldTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new FileConfigurator('/project');
    }

    public function testRelativeUploadDirIsResolvedFromProjectDir(): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('/project/public/uploads/files/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testAbsoluteUploadDirIsKeptAsIs(): void
    {
        // absolute paths must not be re-rooted under the project dir. See issue #7459.
        $field = FileField::new('document')->setUploadDir('/mnt/data/uploads');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('/mnt/data/uploads/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testStreamWrapperUploadDirIsKeptAsIs(): void
    {
        $field = FileField::new('document')->setUploadDir('s3://bucket/uploads');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('s3://bucket/uploads/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testMissingUploadDirThrows(): void
    {
        $field = FileField::new('document');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('setUploadDir()');
        $this->configure($field, Crud::PAGE_EDIT);
    }
}
