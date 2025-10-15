<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterInterface;
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
    private bool $multiple;
    private UploadedFileAdapterInterface $uploadedFileAdapter;

    public function __construct(callable $uploadFilename, callable $uploadValidate, bool $multiple, UploadedFileAdapterInterface $uploadedFileAdapter)
    {
        $this->uploadFilename = $uploadFilename;
        $this->uploadValidate = $uploadValidate;
        $this->multiple = $multiple;
        $this->uploadedFileAdapter = $uploadedFileAdapter;
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
            return null;
        }

        if (!$this->multiple) {
            return $this->doReverseTransform($value);
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array or null.');
        }

        return array_map([$this, 'doReverseTransform'], $value);
    }

    private function doTransform(mixed $value): ?File
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof File) {
            return $value;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if ($this->uploadedFileAdapter->supports($value)) {
            return $this->uploadedFileAdapter->create($value);
        }

        return null;
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

            return ($this->uploadValidate)($filename, $this->uploadedFileAdapter);
        }

        if ($value instanceof File) {
            return $value->getFilename();
        }

        throw new TransformationFailedException('Expected an instance of File or null.');
    }
}
