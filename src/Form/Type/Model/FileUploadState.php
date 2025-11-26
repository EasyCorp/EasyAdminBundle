<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FileUploadState
{
    /** @var File[] */
    private array $currentFiles = [];

    /** @var UploadedFile[] */
    private array $uploadedFiles = [];

    private bool $delete = false;

    public function __construct(private bool $allowAdd = false)
    {
    }

    /**
     * @return File[]
     */
    public function getCurrentFiles(): array
    {
        return $this->currentFiles;
    }

    /**
     * @param File|array<File>|null $currentFiles
     */
    public function setCurrentFiles(/* File|array|null */ $currentFiles): void
    {
        if (null !== $currentFiles && !\is_array($currentFiles) && !$currentFiles instanceof File) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$currentFiles',
                __METHOD__,
                '"array" or "File" or "null"',
                \gettype($currentFiles)
            );
        }

        if (null === $currentFiles) {
            $currentFiles = [];
        }

        if (!\is_array($currentFiles)) {
            $currentFiles = [$currentFiles];
        }

        $this->currentFiles = $currentFiles;
    }

    public function hasCurrentFiles(): bool
    {
        return [] !== $this->currentFiles;
    }

    /**
     * @return UploadedFile[]
     */
    public function getUploadedFiles(): iterable
    {
        if ($this->allowAdd) {
            $index = \count($this->currentFiles);
        } else {
            $index = 0;
        }

        foreach ($this->uploadedFiles as $uploadedFile) {
            yield $index++ => $uploadedFile;
        }
    }

    /**
     * @param UploadedFile[]|UploadedFile|null $uploadedFiles
     */
    public function setUploadedFiles(/* UploadedFile|array|null */ $uploadedFiles): void
    {
        if (null !== $uploadedFiles && !\is_array($uploadedFiles) && !$uploadedFiles instanceof UploadedFile) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$uploadedFiles',
                __METHOD__,
                '"array" or "UploadedFile" or "null"',
                \gettype($uploadedFiles)
            );
        }

        if (null === $uploadedFiles) {
            $uploadedFiles = [];
        }

        if (!\is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        $this->uploadedFiles = $uploadedFiles;
    }

    public function hasUploadedFiles(): bool
    {
        return [] !== $this->uploadedFiles;
    }

    public function isAddAllowed(): bool
    {
        return $this->allowAdd;
    }

    public function setAllowAdd(bool $allowAdd): void
    {
        $this->allowAdd = $allowAdd;
    }

    public function isDelete(): bool
    {
        return $this->delete;
    }

    public function setDelete(bool $delete): void
    {
        $this->delete = $delete;
    }

    public function isModified(): bool
    {
        return [] !== $this->uploadedFiles || $this->delete;
    }
}
