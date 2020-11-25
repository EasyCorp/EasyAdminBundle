<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class StringToFileTransformer implements DataTransformerInterface
{
    private $uploadDir;
    private $uploadFilename;
    private $uploadValidate;
    private $multiple;

    public function __construct(string $uploadDir, callable $uploadFilename, callable $uploadValidate, bool $multiple)
    {
        $this->uploadDir = $uploadDir;
        $this->uploadFilename = $uploadFilename;
        $this->uploadValidate = $uploadValidate;
        $this->multiple = $multiple;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
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

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
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

    private function doTransform($value): ?File
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof File) {
            return $value;
        }

        if (\is_string($value)) {
            return new File($this->uploadDir.$value);
        }

        throw new TransformationFailedException('Expected a string or null.');
    }

    private function doReverseTransform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof UploadedFile) {
            if (!$value->isValid()) {
                throw new TransformationFailedException($value->getErrorMessage());
            }

            $filename = ($this->uploadFilename)($value);

            return ($this->uploadValidate)($filename);
        }

        if ($value instanceof File) {
            return $value->getFilename();
        }

        throw new TransformationFailedException('Expected an instance of File or null.');
    }
}
