<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\FileConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FlysystemFile;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTest;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGeneratePublicUrl;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileConfiguratorFlysystemTest extends AbstractFieldTest
{
    private FilesystemOperator&MockObject $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->getMockBuilder(FilesystemOperator::class)
            ->addMethods(['publicUrl'])
            ->getMockForAbstractClass();

        $locator = $this->createMock(ContainerInterface::class);
        $locator->method('has')->willReturnCallback(static fn (string $id) => 'default.storage' === $id);
        $locator->method('get')->willReturnCallback(fn (string $id) => match ($id) {
            'default.storage' => $this->filesystem,
            default => throw new \RuntimeException("Unknown storage: $id"),
        });

        $this->configurator = new FileConfigurator('/project', $locator);
    }

    // --- URL generation (INDEX page) ---

    public function testSingleFileUrlWithPrefix(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue('photos/cat.jpg');

        $dto = $this->configure($field);

        $this->assertSame('https://cdn.example.com/photos/cat.jpg', $dto->getFormattedValue());
    }

    public function testMultipleFilesUrlWithPrefix(): void
    {
        $field = FileField::new('photos')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue(['a.jpg', 'b.jpg']);

        $dto = $this->configure($field);

        $this->assertSame([
            'https://cdn.example.com/a.jpg',
            'https://cdn.example.com/b.jpg',
        ], $dto->getFormattedValue());
    }

    public function testNullValueSetsEmptyTemplate(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue(null);

        $dto = $this->configure($field);

        $this->assertNull($dto->getFormattedValue());
        $this->assertSame('label/empty', $dto->getTemplateName());
    }

    public function testAbsoluteUrlReturnedAsIs(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue('https://other.com/image.jpg');

        $dto = $this->configure($field);

        $this->assertSame('https://other.com/image.jpg', $dto->getFormattedValue());
    }

    public function testTrailingSlashNormalization(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com/')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue('photos/cat.jpg');

        $dto = $this->configure($field);

        $this->assertSame('https://cdn.example.com/photos/cat.jpg', $dto->getFormattedValue());
    }

    public function testUrlDerivedFromFlysystemPublicUrlWhenPrefixMissing(): void
    {
        $this->filesystem->expects($this->once())
            ->method('publicUrl')
            ->with('photos/cat.jpg')
            ->willReturn('https://flysystem.example.com/photos/cat.jpg');

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue('photos/cat.jpg');

        $dto = $this->configure($field);

        $this->assertSame('https://flysystem.example.com/photos/cat.jpg', $dto->getFormattedValue());
    }

    public function testUrlPrefixOverridesFlysystemPublicUrl(): void
    {
        $this->filesystem->expects($this->never())->method('publicUrl');

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://override.example.com')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue('photos/cat.jpg');

        $dto = $this->configure($field);

        $this->assertSame('https://override.example.com/photos/cat.jpg', $dto->getFormattedValue());
    }

    public function testMissingPrefixAndUnconfiguredPublicUrlThrows(): void
    {
        $this->filesystem->method('publicUrl')
            ->willThrowException(UnableToGeneratePublicUrl::noGeneratorConfigured('photos/cat.jpg'));

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setUploadDir('photos/');
        $field->getAsDto()->setValue('photos/cat.jpg');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('setFlysystemUrlPrefix');
        $this->configure($field);
    }

    // --- Form type options (EDIT page) ---

    public function testUploadDirIsFlysystemPath(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('photos/', $dto->getFormTypeOption('upload_dir'));
    }

    public function testFlysystemStorageOption(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame($this->filesystem, $dto->getFormTypeOption('flysystem_storage'));
    }

    public function testFlysystemUrlPrefixPassedThrough(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('https://cdn.example.com', $dto->getFormTypeOption('flysystem_url_prefix'));
    }

    public function testDownloadPathSetToNull(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertNull($dto->getFormTypeOption('download_path'));
    }

    public function testUploadCallablesAreSet(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertIsCallable($dto->getFormTypeOption('upload_new'));
        $this->assertIsCallable($dto->getFormTypeOption('upload_delete'));
        $this->assertIsCallable($dto->getFormTypeOption('upload_validate'));
    }

    // --- upload_new callable ---

    public function testUploadNewCallsWriteStream(): void
    {
        $this->filesystem->expects($this->once())
            ->method('writeStream')
            ->with(
                'photos/report.pdf',
                $this->isType('resource')
            );

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_test_');
        file_put_contents($tmpFile, 'test content');

        $uploaded = new UploadedFile($tmpFile, 'report.pdf', 'application/pdf', null, true);

        $uploadNew = $dto->getFormTypeOption('upload_new');
        $uploadNew($uploaded, 'photos/', 'report.pdf');

        @unlink($tmpFile);
    }

    // --- upload_delete callable ---

    public function testUploadDeleteCallsDelete(): void
    {
        $this->filesystem->expects($this->once())
            ->method('delete')
            ->with('photos/cat.jpg');

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $uploadDelete = $dto->getFormTypeOption('upload_delete');
        $uploadDelete(new FlysystemFile('photos/cat.jpg'));
    }

    public function testUploadDeleteSwallowsExceptions(): void
    {
        $this->filesystem->method('delete')
            ->willThrowException(new \RuntimeException('Network error'));

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $uploadDelete = $dto->getFormTypeOption('upload_delete');
        // Should not throw
        $uploadDelete(new FlysystemFile('photos/cat.jpg'));

        $this->addToAssertionCount(1);
    }

    // --- upload_validate callable ---

    public function testUploadValidateFileDoesNotExistReturnsUnchanged(): void
    {
        $this->filesystem->method('fileExists')->willReturn(false);

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $validate = $dto->getFormTypeOption('upload_validate');
        $this->assertSame('doc.pdf', $validate('doc.pdf'));
    }

    public function testUploadValidateFileExistsAppendsIndex(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturnCallback(static fn (string $path) => match ($path) {
                'doc.pdf' => true,
                'doc_1.pdf' => false,
                default => false,
            });

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $validate = $dto->getFormTypeOption('upload_validate');
        $this->assertSame('doc_1.pdf', $validate('doc.pdf'));
    }

    public function testUploadValidatePreservesDirectoryPrefix(): void
    {
        $this->filesystem->method('fileExists')
            ->willReturnCallback(static fn (string $path) => match ($path) {
                'uploads/doc.pdf' => true,
                'uploads/doc_1.pdf' => false,
                default => false,
            });

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $validate = $dto->getFormTypeOption('upload_validate');
        $this->assertSame('uploads/doc_1.pdf', $validate('uploads/doc.pdf'));
    }

    // --- Error paths ---

    public function testFlysystemStorageNotFoundInLocatorThrows(): void
    {
        $field = FileField::new('photo')
            ->setFlysystemStorage('unknown.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $this->expectException(\InvalidArgumentException::class);
        $this->configure($field);
    }

    public function testNullLocatorThrows(): void
    {
        $configurator = new FileConfigurator('/project', null);

        $field = FileField::new('photo')
            ->setFlysystemStorage('default.storage')
            ->setFlysystemUrlPrefix('https://cdn.example.com')
            ->setUploadDir('photos/');

        $this->configurator = $configurator;

        $this->expectException(\InvalidArgumentException::class);
        $this->configure($field);
    }
}
