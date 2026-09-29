<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
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
        ];
    }

    public function testRelativeUploadDirIsResolvedFromProjectDir(): void
    {
        $form = $this->factory->create(FileUploadType::class, null, [
            'upload_dir' => 'public/uploads/files',
        ]);

        $this->assertSame($this->projectDir.'/public/uploads/files/', $form->getConfig()->getOption('upload_dir'));
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
}
