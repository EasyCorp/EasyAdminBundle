<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model;

/**
 * Lightweight DTO that replaces Symfony's File object when files are stored
 * in a remote filesystem via Flysystem. Exposes the minimal interface needed
 * by EasyAdmin templates and form types.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class FlysystemFile
{
    public function __construct(
        private readonly string $path,
        private readonly ?string $filename = null,
        private readonly ?int $size = null,
    ) {
    }

    public function getPathname(): string
    {
        return $this->path;
    }

    public function getFilename(): string
    {
        return $this->filename ?? basename($this->path);
    }

    public function getSize(): ?int
    {
        return $this->size;
    }
}
