<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToFileTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StringToFileTransformerTest extends TestCase
{
    private string $uploadDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->uploadDir = sys_get_temp_dir().'/ea_stf_'.bin2hex(random_bytes(6)).'/';
        $this->filesystem->mkdir($this->uploadDir.'subdir');
        file_put_contents($this->uploadDir.'normal.txt', 'hello');
        file_put_contents($this->uploadDir.'subdir/normal.txt', 'hello');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->uploadDir);
    }

    public function testTransformReturnsNullForMissingFile(): void
    {
        $transformer = $this->createTransformer();

        self::assertNull($transformer->transform('does-not-exist.txt'));
    }

    /**
     * @dataProvider cleanRelativePathProvider
     */
    public function testTransformReturnsFileForCleanRelativePath(string $value): void
    {
        $transformer = $this->createTransformer();

        $result = $transformer->transform($value);

        self::assertInstanceOf(File::class, $result);
    }

    public static function cleanRelativePathProvider(): iterable
    {
        yield 'bare filename' => ['normal.txt'];
        yield 'relative subpath' => ['subdir/normal.txt'];
    }

    /**
     * @dataProvider unsafeStoredPathProvider
     */
    public function testTransformRejectsUnsafeStoredPath(string $value): void
    {
        $transformer = $this->createTransformer();

        self::assertNull($transformer->transform($value));
    }

    public static function unsafeStoredPathProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'parent traversal' => ['../../../etc/passwd'];
        yield 'parent traversal inside subpath' => ['subdir/../../../etc/passwd'];
        yield 'unix absolute path' => ['/etc/passwd'];
        yield 'windows drive absolute' => ['C:/Windows/win.ini'];
        yield 'windows backslash drive absolute' => ['C:\\Windows\\win.ini'];
        yield 'backslash parent traversal' => ['sub\\..\\..\\etc'];
        yield 'null byte' => ["file\0.txt"];
        yield 'leading backslash' => ['\\etc\\passwd'];
    }

    public function testReverseTransformPassesFullPathToUploadValidate(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_stf_');
        file_put_contents($tmpFile, 'new content');
        $uploaded = new UploadedFile($tmpFile, 'normal.txt', 'text/plain', null, true);

        $receivedByValidate = null;
        $transformer = new StringToFileTransformer(
            $this->uploadDir,
            static fn (UploadedFile $file): string => $file->getClientOriginalName(),
            static function (string $filename) use (&$receivedByValidate): string {
                $receivedByValidate = $filename;

                return $filename;
            },
            false,
        );

        $transformer->reverseTransform($uploaded);

        self::assertSame($this->uploadDir.'normal.txt', $receivedByValidate);

        @unlink($tmpFile);
    }

    public function testReverseTransformStripsUploadDirFromValidateResult(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_stf_');
        file_put_contents($tmpFile, 'new content');
        $uploaded = new UploadedFile($tmpFile, 'normal.txt', 'text/plain', null, true);

        // Default-style validator: if the file exists on disk, append a numeric
        // suffix to avoid overwriting the existing file.
        $uploadValidate = static function (string $filename): string {
            if (!file_exists($filename)) {
                return $filename;
            }

            $index = 1;
            $pathInfo = pathinfo($filename);
            while (file_exists($filename = sprintf('%s/%s_%d.%s', $pathInfo['dirname'], $pathInfo['filename'], $index, $pathInfo['extension']))) {
                ++$index;
            }

            return $filename;
        };

        $transformer = new StringToFileTransformer(
            $this->uploadDir,
            static fn (UploadedFile $file): string => $file->getClientOriginalName(),
            $uploadValidate,
            false,
        );

        // "normal.txt" already exists in the upload dir (see setUp), so the
        // validator must rename the incoming file to avoid an overwrite, and
        // the returned value must be the relative filename (no upload dir).
        $result = $transformer->reverseTransform($uploaded);

        self::assertSame('normal_1.txt', $result);

        @unlink($tmpFile);
    }

    private function createTransformer(): StringToFileTransformer
    {
        return new StringToFileTransformer(
            $this->uploadDir,
            static fn ($file) => 'noop',
            static fn (string $filename) => $filename,
            false,
        );
    }
}
