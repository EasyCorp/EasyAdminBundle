<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FileUploadState
{
    /** @var array<File|FlysystemFile> */
    private array $currentFiles = [];

    /** @var UploadedFile[] */
    private array $uploadedFiles = [];

    private bool $delete = false;

    /** @var string[] */
    private array $deletedFiles = [];

    public function __construct(private bool $allowAdd = false)
    {
    }

    /**
     * @return array<File|FlysystemFile>
     */
    public function getCurrentFiles(): array
    {
        return $this->currentFiles;
    }

    /**
     * @param File|FlysystemFile|array<File|FlysystemFile>|null $currentFiles
     */
    public function setCurrentFiles(File|FlysystemFile|array|null $currentFiles): void
    {
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
     * @param UploadedFile|UploadedFile[]|null $uploadedFiles
     */
    public function setUploadedFiles(UploadedFile|array|null $uploadedFiles): void
    {
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

    /**
     * @return string[]
     */
    public function getDeletedFiles(): array
    {
        return $this->deletedFiles;
    }

    /**
     * @param string[] $deletedFiles
     */
    public function setDeletedFiles(array $deletedFiles): void
    {
        $this->deletedFiles = $deletedFiles;
    }

    public function isModified(): bool
    {
        return [] !== $this->uploadedFiles || $this->delete || [] !== $this->deletedFiles;
    }
}
