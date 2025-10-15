<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Adapter;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\LocalFileAdapter;
use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
class LocalFileAdapterTest extends TestCase
{
    private const PROJECT_DIR = __DIR__.'/../TestApplication';

    private UploadedFileAdapterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new UploadedFileAdapterFactory(self::PROJECT_DIR);
    }

    public function testLocalFileAdapterCanCreateFile()
    {
        $adapter = $this->factory->createLocalFileAdapter('public/uploads/images', 'uploads/images');
        $this->assertInstanceOf(LocalFileAdapter::class, $adapter);
        $this->assertInstanceOf(File::class, $adapter->create('symfony.png'));
        $this->assertTrue($adapter->exists('symfony.png'));
        $this->assertStringContainsString('uploads/images/symfony.png', $adapter->publicUrl('symfony.png'));
    }
}
