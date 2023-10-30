<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Decorator;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\File;

class FlysystemFile extends File
{
    private FilesystemOperator $filesystemOperator;

    public function __construct(FilesystemOperator $filesystemOperator, string $path)
    {
        $this->filesystemOperator = $filesystemOperator;

        parent::__construct($path, false);
    }

    public function getSize(): int
    {
        return $this->filesystemOperator->fileSize($this->getPathname());
    }

    public function getMTime(): int
    {
        return $this->filesystemOperator->lastModified($this->getPathname());
    }
}
