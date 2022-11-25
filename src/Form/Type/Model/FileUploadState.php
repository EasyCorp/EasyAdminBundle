<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FileUploadState
{
    private bool $allowAdd;

    /** @var File[] */
    private array $currentFiles = [];

    /** @var UploadedFile[] */
    private array $uploadedFiles = [];

    private bool $delete = false;

    public function __construct(bool $allowAdd = false)
    {
        $this->allowAdd = $allowAdd;
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
    public function setCurrentFiles($currentFiles): void
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

    public function setUploadedFiles($uploadedFiles): void
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

    public function isModified(): bool
    {
        return [] !== $this->uploadedFiles || $this->delete;
    }
}
