<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Adapter;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use function Symfony\Component\String\u;

/**
 * @author Yoann Chocteau <yoann@chocteau.dev>
 */
class LocalFileAdapter implements UploadedFileAdapterInterface
{
    public function __construct(private string $uploadDir, private string $basePath, private string $projectDir)
    {
        $relativeUploadDir = u($this->uploadDir)->trimStart(\DIRECTORY_SEPARATOR)->ensureEnd(\DIRECTORY_SEPARATOR)->toString();
        $isStreamWrapper = filter_var($relativeUploadDir, \FILTER_VALIDATE_URL);
        if ($isStreamWrapper) {
            $absoluteUploadDir = $relativeUploadDir;
        } else {
            $absoluteUploadDir = u($relativeUploadDir)->ensureStart($this->projectDir.\DIRECTORY_SEPARATOR)->toString();
        }

        $this->uploadDir = $absoluteUploadDir;
    }

    public function supports(string $value): bool
    {
        return is_file($this->uploadDir.$value);
    }

    public function create(string $value): File
    {
        return new File($this->uploadDir.$value);
    }

    public function publicUrl(string $value): string
    {
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (0 !== preg_match('/^(http[s]?|\/\/)/i', $value)) {
            return $value;
        }

        // remove project path from filepath
        $value = str_replace($this->projectDir.\DIRECTORY_SEPARATOR.'public'.\DIRECTORY_SEPARATOR, '', $value);

        return \strlen($this->basePath) > 0
            ? rtrim($this->basePath, '/').'/'.ltrim($value, '/')
            : '/'.ltrim($value, '/');
    }

    public function upload(UploadedFile $file, string $fileName): void
    {
        $file->move($this->uploadDir, $fileName);
    }

    public function delete(File $file): void
    {
        unlink($file->getPathname());
    }

    public function exists(string $value): bool
    {
        return file_exists($this->uploadDir.$value);
    }
}
