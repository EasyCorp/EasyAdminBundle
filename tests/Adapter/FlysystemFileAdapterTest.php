<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Adapter;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\FlysystemFileAdapter;
use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterFactory;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
class FlysystemFileAdapterTest extends TestCase
{
    private const PROJECT_DIR = __DIR__.'/../TestApplication';

    private UploadedFileAdapterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new UploadedFileAdapterFactory(self::PROJECT_DIR);
    }

    public function testFlysystemFileAdapterCanCreateFile()
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        $filesystemOperator->method('fileExists')->willReturn(true);

        /** @var FilesystemOperator $filesystemOperator */
        $adapter = $this->factory->createFlysystemFileAdapter($filesystemOperator);
        $this->assertInstanceOf(FlysystemFileAdapter::class, $adapter);

        $this->assertTrue($adapter->exists('test.jpg'));
        $this->assertInstanceOf(File::class, $adapter->create('test.jpg'));
    }
}
