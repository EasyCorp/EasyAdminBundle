<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Adapter;

use EasyCorp\Bundle\EasyAdminBundle\Decorator\FlysystemFile;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
class FlysystemFileAdapter implements UploadedFileAdapterInterface
{
    public function __construct(private FilesystemOperator $fs)
    {
    }

    public function supports(string $value): bool
    {
        return $this->fs->fileExists($value);
    }

    public function create(string $value): File
    {
        return new FlysystemFile($this->fs, $value);
    }

    public function publicUrl(string $value): string
    {
        if (method_exists($this->fs, 'publicUrl')) {
            return $this->fs->publicUrl($value);
        }
        throw new \RuntimeException('Cannot generate public URL for this storage. Public URL generation was added in 3.6 of Flysystem. Please upgrade your Flysystem version.');
    }

    public function upload(UploadedFile $file, string $fileName): void
    {
        $stream = fopen($file->getPathname(), 'r');
        if (false === $stream) {
            throw new \RuntimeException('Failed to open file stream');
        }
        $this->fs->writeStream($fileName, $stream);
        fclose($stream);
    }

    public function delete(File $file): void
    {
        $this->fs->delete($file->getPathname());
    }

    public function exists(string $value): bool
    {
        return $this->fs->fileExists($value);
    }
}
