<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Adapter;

use League\Flysystem\FilesystemOperator;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
class UploadedFileAdapterFactory
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function createLocalFileAdapter(string $uploadDir, string $basePath): LocalFileAdapter
    {
        return new LocalFileAdapter($uploadDir, $basePath, $this->projectDir);
    }

    public function createFlysystemFileAdapter(FilesystemOperator $filesystemOperator): FlysystemFileAdapter
    {
        return new FlysystemFileAdapter($filesystemOperator);
    }
}
