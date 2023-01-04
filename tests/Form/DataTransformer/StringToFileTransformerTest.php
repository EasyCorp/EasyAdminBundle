<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Decorator\FlysystemFile;
use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToFileTransformer;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

class StringToFileTransformerTest extends TestCase
{

    public function testTransform(): void
    {
        $uploadFilename = static fn($value) => 'foo';
        $uploadValidate = static fn($filename) => 'foo';
        $filesystemOperatorMock = $this->createStub(FilesystemOperator::class);
        $filesystemOperatorMock
            ->method('fileExists')
            ->willReturn(true)
        ;

        $stringToFileTransformer = new StringToFileTransformer(null, $uploadFilename, $uploadValidate, false, $filesystemOperatorMock);

        $transformedFile = $stringToFileTransformer->transform('bar');

        self::assertInstanceOf(FlysystemFile::class, $transformedFile);
    }
}
