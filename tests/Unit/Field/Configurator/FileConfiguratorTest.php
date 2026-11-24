<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\FileConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

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

    public function testPatternNamedLikeAPhpFunctionIsNotCalled(): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files')->setUploadedFileNamePattern('time');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertSame('time', $dto->getFormTypeOption('upload_filename'));
    }

    public function testPatternDefinedAsClosureReceivesTheEntity(): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files')
            ->setUploadedFileNamePattern(static fn (UploadedFile $file, object $entity): string => 'custom-name');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->assertInstanceOf(\Closure::class, $dto->getFormTypeOption('upload_filename'));
    }

    public function testMissingUploadDirThrows(): void
    {
        $field = FileField::new('document');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('setUploadDir()');
        $this->configure($field, Crud::PAGE_EDIT);
    }

    #[TestWith(['.ofx', ['ofx']])]
    #[TestWith(['.OFX,.cfg', ['ofx', 'cfg']])]
    public function testMimeTypesWithOnlyUnknownExtensionsRestrictTheFileExtension(string $mimeTypes, array $expectedExtensions): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files')->mimeTypes($mimeTypes);

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $constraint = $this->getLastFileConstraint($dto->getFormTypeOption('file_constraints'));
        $this->assertSame($expectedExtensions, $constraint->extensions);
        $this->assertSame([], (array) $constraint->mimeTypes);
    }

    public function testMimeTypesWithKnownAndUnknownExtensionsRestrictTheMimeTypes(): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files')->mimeTypes('.pdf,.ofx');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $constraint = $this->getLastFileConstraint($dto->getFormTypeOption('file_constraints'));
        $this->assertContains('application/pdf', $constraint->mimeTypes);
        $this->assertSame([], (array) $constraint->extensions);
    }

    public function testMimeTypesWithOnlyUnknownExtensionsStoreTheFileNameExtension(): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files')->mimeTypes('.ofx');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $uploadFilename = $dto->getFormTypeOption('upload_filename');
        $this->assertSame('[name].ofx', $uploadFilename($this->createUploadedFile('statement.OFX')));
        $this->assertSame('[name].[extension]', $uploadFilename($this->createUploadedFile('statement.html')));
    }

    public function testMimeTypesWithOnlyUnknownExtensionsRejectGeneratedNamesWithOtherExtensions(): void
    {
        $field = FileField::new('document')->setUploadDir('public/uploads/files')->mimeTypes('.ofx')
            ->setUploadedFileNamePattern(static fn (UploadedFile $file): string => 'statement.html');

        $dto = $this->configure($field, Crud::PAGE_EDIT);

        $this->expectException(TransformationFailedException::class);
        $dto->getFormTypeOption('upload_filename')($this->createUploadedFile('statement.ofx'));
    }

    private function createUploadedFile(string $clientName): UploadedFile
    {
        return new UploadedFile(__FILE__, $clientName, null, null, true);
    }

    private function getLastFileConstraint(array $constraints): FileConstraint
    {
        $constraint = end($constraints);
        $this->assertInstanceOf(FileConstraint::class, $constraint);

        return $constraint;
    }
}
