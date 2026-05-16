<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToFileTransformer;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FlysystemFile;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StringToFileTransformerFlysystemTest extends TestCase
{
    private function createTransformer(
        FilesystemOperator $filesystem,
        bool $multiple = false,
        ?callable $uploadFilename = null,
        ?callable $uploadValidate = null,
    ): StringToFileTransformer {
        return new StringToFileTransformer(
            uploadDir: 'uploads/',
            uploadFilename: $uploadFilename ?? static fn (UploadedFile $f): string => $f->getClientOriginalName(),
            uploadValidate: $uploadValidate ?? static fn (string $filename): string => $filename,
            multiple: $multiple,
            flysystemStorage: $filesystem,
        );
    }

    // --- transform() tests ---

    public function testTransformNullReturnsNull(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        $this->assertNull($this->createTransformer($fs)->transform(null));
    }

    public function testTransformStringFileExistsReturnsFlysystemFileWithSize(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->with('photos/cat.jpg')->willReturn(true);
        $fs->method('fileSize')->with('photos/cat.jpg')->willReturn(1234);

        $result = $this->createTransformer($fs)->transform('photos/cat.jpg');

        $this->assertInstanceOf(FlysystemFile::class, $result);
        $this->assertSame('photos/cat.jpg', $result->getPathname());
        $this->assertSame(1234, $result->getSize());
    }

    public function testTransformStringFileExistsFileSizeThrowsReturnsFlysystemFileWithNullSize(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->with('photos/cat.jpg')->willReturn(true);
        $fs->method('fileSize')->willThrowException(new \RuntimeException('Cannot read size'));

        $result = $this->createTransformer($fs)->transform('photos/cat.jpg');

        $this->assertInstanceOf(FlysystemFile::class, $result);
        $this->assertSame('photos/cat.jpg', $result->getPathname());
        $this->assertNull($result->getSize());
    }

    public function testTransformStringFileDoesNotExistReturnsNull(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->with('photos/missing.jpg')->willReturn(false);

        $this->assertNull($this->createTransformer($fs)->transform('photos/missing.jpg'));
    }

    public function testTransformStringFileExistsThrowsReturnsNull(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->willThrowException(new \RuntimeException('Connection error'));

        $this->assertNull($this->createTransformer($fs)->transform('photos/cat.jpg'));
    }

    public function testTransformExistingFlysystemFileReturnedAsIs(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $existing = new FlysystemFile('photos/cat.jpg', null, 999);

        $result = $this->createTransformer($fs)->transform($existing);

        $this->assertSame($existing, $result);
    }

    public function testTransformMultipleModeWithArrayOfStrings(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('fileExists')->willReturn(true);
        $fs->method('fileSize')->willReturn(100);

        $result = $this->createTransformer($fs, multiple: true)->transform(['a.jpg', 'b.jpg']);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(FlysystemFile::class, $result[0]);
        $this->assertSame('a.jpg', $result[0]->getPathname());
        $this->assertInstanceOf(FlysystemFile::class, $result[1]);
        $this->assertSame('b.jpg', $result[1]->getPathname());
    }

    // --- reverseTransform() tests ---

    public function testReverseTransformNullReturnsEmptyString(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        $this->assertSame('', $this->createTransformer($fs)->reverseTransform(null));
    }

    public function testReverseTransformNullReturnsEmptyArrayWhenMultiple(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        $this->assertSame([], $this->createTransformer($fs, multiple: true)->reverseTransform(null));
    }

    public function testReverseTransformFlysystemFileReturnsPathname(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $file = new FlysystemFile('photos/cat.jpg');

        $result = $this->createTransformer($fs)->reverseTransform($file);

        $this->assertSame('photos/cat.jpg', $result);
    }

    public function testReverseTransformValidUploadedFileCallsCallables(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_test_');
        file_put_contents($tmpFile, 'test content');

        $uploaded = new UploadedFile($tmpFile, 'report.pdf', 'application/pdf', null, true);

        $filenameCalled = false;
        $validateCalled = false;

        $transformer = $this->createTransformer(
            $fs,
            uploadFilename: static function (UploadedFile $f) use (&$filenameCalled): string {
                $filenameCalled = true;

                return 'custom_'.$f->getClientOriginalName();
            },
            uploadValidate: static function (string $filename) use (&$validateCalled): string {
                $validateCalled = true;

                return $filename;
            },
        );

        $result = $transformer->reverseTransform($uploaded);

        $this->assertTrue($filenameCalled);
        $this->assertTrue($validateCalled);
        $this->assertSame('custom_report.pdf', $result);

        @unlink($tmpFile);
    }

    public function testReverseTransformInvalidUploadedFileThrows(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ea_test_');
        file_put_contents($tmpFile, 'test');

        // error code 1 = UPLOAD_ERR_INI_SIZE → invalid
        $uploaded = new UploadedFile($tmpFile, 'report.pdf', 'application/pdf', \UPLOAD_ERR_INI_SIZE, false);

        $this->expectException(TransformationFailedException::class);
        $this->createTransformer($fs)->reverseTransform($uploaded);

        @unlink($tmpFile);
    }
}
