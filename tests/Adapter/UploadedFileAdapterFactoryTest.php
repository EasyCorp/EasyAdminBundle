<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Adapter;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\FlysystemFileAdapter;
use EasyCorp\Bundle\EasyAdminBundle\Adapter\LocalFileAdapter;
use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterFactory;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
class UploadedFileAdapterFactoryTest extends TestCase
{
    private const PROJECT_DIR = __DIR__.'/../TestApplication';

    private UploadedFileAdapterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new UploadedFileAdapterFactory(self::PROJECT_DIR);
    }

    public function testCreateLocalFileAdapter()
    {
        $adapter = $this->factory->createLocalFileAdapter('/public/uploads/images', '/uploads/images');
        $this->assertInstanceOf(LocalFileAdapter::class, $adapter);
    }

    public function testCreateFlysystemFileAdapter()
    {
        $filesystemOperator = $this->createMock(FilesystemOperator::class);
        /** @var FilesystemOperator $filesystemOperator */
        $adapter = $this->factory->createFlysystemFileAdapter($filesystemOperator);
        $this->assertInstanceOf(FlysystemFileAdapter::class, $adapter);
    }
}
