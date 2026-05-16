<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Form\Type\Model;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FlysystemFile;
use PHPUnit\Framework\TestCase;

class FlysystemFileTest extends TestCase
{
    public function testGetPathname(): void
    {
        $file = new FlysystemFile('photos/cat.jpg');

        $this->assertSame('photos/cat.jpg', $file->getPathname());
    }

    public function testGetFilenameExplicit(): void
    {
        $file = new FlysystemFile('photos/cat.jpg', 'my-cat.jpg');

        $this->assertSame('my-cat.jpg', $file->getFilename());
    }

    public function testGetFilenameDerivedFromPath(): void
    {
        $file = new FlysystemFile('photos/cat.jpg');

        $this->assertSame('cat.jpg', $file->getFilename());
    }

    public function testGetSize(): void
    {
        $file = new FlysystemFile('photos/cat.jpg', null, 1234);

        $this->assertSame(1234, $file->getSize());
    }

    public function testGetSizeReturnsNullByDefault(): void
    {
        $file = new FlysystemFile('photos/cat.jpg');

        $this->assertNull($file->getSize());
    }
}
