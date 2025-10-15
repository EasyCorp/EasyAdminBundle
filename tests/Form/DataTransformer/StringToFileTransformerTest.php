<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Decorator\FlysystemFile;
use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToFileTransformer;
use PHPUnit\Framework\TestCase;

class StringToFileTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $uploadFilename = static fn ($value) => 'foo';
        $uploadValidate = static fn ($filename) => 'foo';

        $uploadedFileAdapterMock = $this->createStub(UploadedFileAdapterInterface::class);
        $uploadedFileAdapterMock
            ->method('upload')
            ->willReturn(true)
        ;

        /** @var UploadedFileAdapterInterface $uploadedFileAdapterMock */
        $stringToFileTransformer = new StringToFileTransformer($uploadFilename, $uploadValidate, false, $uploadedFileAdapterMock);

        $transformedFile = $stringToFileTransformer->transform('bar');

        self::assertInstanceOf(FlysystemFile::class, $transformedFile);
    }
}
