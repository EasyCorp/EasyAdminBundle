<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validation;

class FileUploadTypeTest extends TypeTestCase
{
    private string $projectDir;
    private string $externalDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/ea_test_project_'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/public/uploads/files', 0777, true);
        $this->externalDir = sys_get_temp_dir().'/ea_test_external_'.bin2hex(random_bytes(4));
        mkdir($this->externalDir, 0777, true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        foreach ([$this->projectDir, $this->externalDir] as $dir) {
            if (is_dir($dir)) {
                $filesystem->remove($dir);
            }
        }
    }

    protected function getExtensions(?ViolationMapperInterface $violationMapper = null): array
    {
        $type = new FileUploadType($this->projectDir, new Filesystem());
        $validator = Validation::createValidator();

        return [
            new PreloadedExtension([$type], []),
            new ValidatorExtension($validator),
            new HttpFoundationExtension(),
        ];
    }

    public function testRelativeUploadDirIsResolvedFromProjectDir(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'public/uploads/files',
        ]);

        $this->assertSame($this->projectDir.'/public/uploads/files/', $form->getConfig()->getOption('upload_dir'));
    }

    public function testUploadFilenameNamedLikeAPhpFunctionIsNotCalled(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'public/uploads/files',
            'upload_filename' => 'basename',
        ]);

        $filePath = $this->externalDir.'/report.txt';
        file_put_contents($filePath, 'some contents');
        $uploadFilename = $form->getConfig()->getOption('upload_filename');

        $this->assertSame('basename', $uploadFilename(new UploadedFile($filePath, 'report.txt', null, null, true)));
    }

    public function testAbsoluteUploadDirOutsideProjectDirIsKeptAsIs(): void
    {
        // absolute paths outside the project dir must not be re-rooted under it. See issue #7459.
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => $this->externalDir,
        ]);

        $this->assertSame($this->externalDir.'/', $form->getConfig()->getOption('upload_dir'));
    }

    public function testAbsoluteUploadDirInsideProjectDirIsNotPrefixedAgain(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => $this->projectDir.'/public/uploads/files/',
        ]);

        $this->assertSame($this->projectDir.'/public/uploads/files/', $form->getConfig()->getOption('upload_dir'));
    }

    public function testDownloadPathIsRelativeToPublicDir(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => $this->projectDir.'/public/uploads/files/',
        ]);

        $this->assertSame('uploads/files/', $form->getConfig()->getOption('download_path'));
    }

    public function testDownloadPathIsNullForUploadDirsOutsidePublicDir(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => $this->externalDir,
        ]);

        $this->assertNull($form->getConfig()->getOption('download_path'));
    }

    public function testExplicitDownloadPathIsKept(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => $this->externalDir,
            'download_path' => 'custom/path/',
        ]);

        $this->assertSame('custom/path/', $form->getConfig()->getOption('download_path'));
    }

    #[DataProvider('provideDeletionSubmissions')]
    public function testSubmittedDeletionIsIgnoredWhenDeleteIsNotAllowed(bool $multiple, array $submittedData): void
    {
        touch($this->projectDir.'/public/uploads/files/foo.txt');
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'public/uploads/files',
            'multiple' => $multiple,
            'allow_delete' => false,
        ]);
        $form->setData($multiple ? ['foo.txt'] : 'foo.txt');

        $form->submit($submittedData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($multiple ? ['foo.txt'] : 'foo.txt', $form->getData());
        $this->assertFalse($form->getConfig()->getAttribute('state')->isModified());
    }

    #[DataProvider('provideDeletionSubmissions')]
    public function testSubmittedDeletionIsAppliedWhenDeleteIsAllowed(bool $multiple, array $submittedData): void
    {
        touch($this->projectDir.'/public/uploads/files/foo.txt');
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'public/uploads/files',
            'multiple' => $multiple,
        ]);
        $form->setData($multiple ? ['foo.txt'] : 'foo.txt');

        $form->submit($submittedData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($multiple ? [] : null, $form->getData());
        $this->assertTrue($form->getConfig()->getAttribute('state')->isModified());
    }

    #[DataProvider('provideParentValidationGroups')]
    public function testFileConstraintsRunWithTheValidationGroupsOfTheParentForm(mixed $parentValidationGroups, array $options, bool $expectedIsValid): void
    {
        $form = $this->factory->createBuilder(FormType::class, null, ['validation_groups' => $parentValidationGroups])
            ->add('document', FileUploadType::class, $options + [
                'upload_dir' => $this->externalDir,
                'file_constraints' => [new File(mimeTypes: ['application/pdf'])],
            ])
            ->getForm();

        $filePath = $this->externalDir.'/document.pdf';
        file_put_contents($filePath, '<!DOCTYPE html><html><body>Document 1</body></html>');
        $file = new UploadedFile($filePath, 'document.pdf', 'application/pdf', null, true);

        $form->submit(['document' => ['file' => ($options['multiple'] ?? false) ? [$file] : $file]]);

        $this->assertSame($expectedIsValid, $form->isValid());
        $this->assertCount($expectedIsValid ? 0 : 1, $form->get('document')->getErrors(true));
    }

    public static function provideParentValidationGroups(): iterable
    {
        yield 'no groups' => [null, [], false];
        yield 'Default group' => [['Default'], [], false];
        yield 'custom group' => [['group1'], [], false];
        yield 'custom group and multiple files' => [['group1'], ['multiple' => true], false];
        yield 'custom group as a string' => ['group1', [], false];
        yield 'custom group returned by a callable' => [static fn (FormInterface $form): array => ['group1'], [], false];
        yield 'group sequence' => [new GroupSequence(['group1', ['group2', 'group3']]), [], false];
        yield 'validation disabled' => [false, [], true];
        yield 'groups defined in the field' => [['group1'], ['validation_groups' => ['group1']], true];
    }

    public static function provideDeletionSubmissions(): iterable
    {
        yield 'single file, delete checkbox' => [false, ['delete' => '1']];
        yield 'single file, deleted files' => [false, ['deleted_files' => '["foo.txt"]']];
        yield 'multiple files, delete checkbox' => [true, ['delete' => '1']];
        yield 'multiple files, deleted files' => [true, ['deleted_files' => '["foo.txt"]']];
    }
}
