<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Adapter;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
interface UploadedFileAdapterInterface
{
    public function supports(string $value): bool;

    public function create(string $value): File;

    public function publicUrl(string $value): string;

    public function upload(UploadedFile $file, string $fileName): void;

    public function delete(File $file): void;

    public function exists(string $value): bool;
}
