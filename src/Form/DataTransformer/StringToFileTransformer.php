<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FlysystemFile;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class StringToFileTransformer implements DataTransformerInterface
{
    /** @var callable */
    private $uploadFilename;
    /** @var callable */
    private $uploadValidate;

    public function __construct(
        private readonly string $uploadDir,
        callable $uploadFilename,
        callable $uploadValidate,
        private readonly bool $multiple,
        private readonly ?FilesystemOperator $flysystemStorage = null,
    ) {
        $this->uploadFilename = $uploadFilename;
        $this->uploadValidate = $uploadValidate;
    }

    public function transform(mixed $value): mixed
    {
        if (null === $value || [] === $value) {
            return null;
        }

        if (!$this->multiple) {
            return $this->doTransform($value);
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array or null.');
        }

        return array_map([$this, 'doTransform'], $value);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || [] === $value) {
            return $this->multiple ? [] : '';
        }

        if (!$this->multiple) {
            return $this->doReverseTransform($value);
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array or null.');
        }

        return array_map([$this, 'doReverseTransform'], $value);
    }

    private function doTransform(mixed $value): File|FlysystemFile|null
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof File || $value instanceof FlysystemFile) {
            return $value;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if (self::isUnsafeStoredPath($value)) {
            return null;
        }

        if (null !== $this->flysystemStorage) {
            try {
                if ($this->flysystemStorage->fileExists($value)) {
                    $size = null;
                    try {
                        $size = $this->flysystemStorage->fileSize($value);
                    } catch (\Throwable) {
                    }

                    return new FlysystemFile($value, null, $size);
                }
            } catch (\Throwable) {
            }

            return null;
        }

        if (is_file($this->uploadDir.$value)) {
            return new File($this->uploadDir.$value);
        }

        return null;
    }

    private static function isUnsafeStoredPath(string $value): bool
    {
        // reject empty values or null bytes
        if ('' === $value || str_contains($value, "\0")) {
            return true;
        }

        // reject absolute paths (Unix, UNC, Windows drive letter)
        $normalized = str_replace('\\', '/', $value);
        if (str_starts_with($normalized, '/') || 1 === preg_match('#^[a-zA-Z]:/#', $normalized)) {
            return true;
        }

        // reject any `..` segment anywhere in the path
        foreach (explode('/', $normalized) as $segment) {
            if ('..' === $segment) {
                return true;
            }
        }

        return false;
    }

    private function doReverseTransform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof UploadedFile) {
            if (!$value->isValid()) {
                throw new TransformationFailedException($value->getErrorMessage());
            }

            $filename = ($this->uploadFilename)($value);
            // Pass the full path (upload dir + filename) to the validator so
            // it can detect existing files and avoid overwriting them. Strip
            // the upload dir afterwards so only the relative name is stored.
            $validatedPath = ($this->uploadValidate)($this->uploadDir.$filename);

            return str_starts_with($validatedPath, $this->uploadDir)
                ? mb_substr($validatedPath, mb_strlen($this->uploadDir))
                : $validatedPath;
        }

        if ($value instanceof FlysystemFile) {
            return $value->getPathname();
        }

        if ($value instanceof File) {
            return str_starts_with($value->getPathname(), $this->uploadDir)
                ? mb_substr($value->getPathname(), mb_strlen($this->uploadDir))
                : $value->getFilename();
        }

        throw new TransformationFailedException('Expected an instance of File, FlysystemFile, or null.');
    }
}
