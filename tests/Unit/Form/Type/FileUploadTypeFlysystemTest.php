<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ReplacedFileBehavior;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

class FileUploadTypeFlysystemTest extends TypeTestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/ea_test_'.bin2hex(random_bytes(4));
        mkdir($this->projectDir.'/public/uploads/files', 0777, true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->projectDir)) {
            (new Filesystem())->remove($this->projectDir);
        }
    }

    protected function getExtensions(): array
    {
        $type = new FileUploadType($this->projectDir, new Filesystem());
        $validator = Validation::createValidator();

        return [
            new PreloadedExtension([$type], []),
            new ValidatorExtension($validator),
        ];
    }

    public function testUploadDirNormalizerSkipsLocalCheckForFlysystem(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        // This should not throw even though the directory doesn't exist locally
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'remote/uploads',
            'flysystem_storage' => $fs,
        ]);

        $this->assertSame('remote/uploads/', $form->getConfig()->getOption('upload_dir'));
    }

    public function testUploadDirNormalizerAddsTrailingSlashForFlysystem(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'remote/uploads',
            'flysystem_storage' => $fs,
        ]);

        $this->assertStringEndsWith('/', $form->getConfig()->getOption('upload_dir'));
    }

    public function testKeepOrFailFlysystemFileDoesNotExistReturnsFilename(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->willReturn(false);

        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'remote/uploads',
            'flysystem_storage' => $fs,
            'replaced_file_behavior' => ReplacedFileBehavior::KEEP_OR_FAIL,
        ]);

        // The KEEP_OR_FAIL callable is wired into the StringToFileTransformer in buildForm.
        // Test it via the model transformer: reverseTransform an UploadedFile → should return the filename.
        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_test_');
        file_put_contents($tmpFile, 'test');
        $uploaded = new UploadedFile($tmpFile, 'test.pdf', 'application/pdf', null, true);

        $transformers = $form->getConfig()->getModelTransformers();
        $result = $transformers[0]->reverseTransform($uploaded);

        $this->assertSame('test.pdf', $result);

        @unlink($tmpFile);
    }

    public function testKeepOrFailFlysystemFileExistsThrows(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->willReturn(true);

        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'remote/uploads',
            'flysystem_storage' => $fs,
            'replaced_file_behavior' => ReplacedFileBehavior::KEEP_OR_FAIL,
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_test_');
        file_put_contents($tmpFile, 'test');
        $uploaded = new UploadedFile($tmpFile, 'test.pdf', 'application/pdf', null, true);

        $transformers = $form->getConfig()->getModelTransformers();

        $this->expectException(TransformationFailedException::class);
        try {
            $transformers[0]->reverseTransform($uploaded);
        } finally {
            @unlink($tmpFile);
        }
    }
}
