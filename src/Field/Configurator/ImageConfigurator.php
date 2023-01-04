<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGeneratePublicUrl;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ImageConfigurator implements FieldConfiguratorInterface
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return ImageField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $configuredBasePath = $field->getCustomOption(ImageField::OPTION_BASE_PATH);
        $filesystemOperator = $field->getCustomOption(ImageField::OPTION_FILESYSTEM_OPERATOR);

        $formattedValue = \is_array($field->getValue())
            ? $this->getImagesPaths($field->getValue(), $configuredBasePath, $filesystemOperator)
            : $this->getImagePath($field->getValue(), $configuredBasePath, $filesystemOperator);
        $field->setFormattedValue($formattedValue);

        $field->setFormTypeOption('upload_filename', $field->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));

        // this check is needed to avoid displaying broken images when image properties are optional
        if (null === $formattedValue || '' === $formattedValue || (\is_array($formattedValue) && 0 === \count($formattedValue)) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $field->setTemplateName('label/empty');
        }

        if (!\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            return;
        }

        if (null !== $relativeUploadDir = $field->getCustomOption(ImageField::OPTION_UPLOAD_DIR)) {
            $relativeUploadDir = u($relativeUploadDir)->trimStart(\DIRECTORY_SEPARATOR)->ensureEnd(\DIRECTORY_SEPARATOR)->toString();
            $isStreamWrapper = filter_var($relativeUploadDir, \FILTER_VALIDATE_URL);
            if ($isStreamWrapper) {
                $absoluteUploadDir = $relativeUploadDir;
            } else {
                $absoluteUploadDir = u($relativeUploadDir)->ensureStart($this->projectDir.\DIRECTORY_SEPARATOR)->toString();
            }
            $field->setFormTypeOption('upload_dir', $absoluteUploadDir);
        }

        if (null !== $filesystemOperator = $field->getCustomOption(ImageField::OPTION_FILESYSTEM_OPERATOR)) {
            $field->setFormTypeOption('filesystem_operator', $filesystemOperator);
        }
    }

    private function getImagesPaths(?array $images, ?string $basePath, ?FilesystemOperator $filesystemOperator): array
    {
        $imagesPaths = [];
        foreach ($images as $image) {
            $imagesPaths[] = $this->getImagePath($image, $basePath, $filesystemOperator);
        }

        return $imagesPaths;
    }

    private function getImagePath(?string $imagePath, ?string $basePath, ?FilesystemOperator $filesystemOperator): ?string
    {
        if (null !==$filesystemOperator && null !== $imagePath) {
            try {
                return $filesystemOperator->publicUrl($imagePath);
            } catch (UnableToGeneratePublicUrl $e) {
                // do nothing : try to get image path with logic below
            }
        }
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null === $imagePath || 0 !== preg_match('/^(http[s]?|\/\/)/i', $imagePath)) {
            return $imagePath;
        }

        // remove project path from filepath
        $imagePath = str_replace($this->projectDir.\DIRECTORY_SEPARATOR.'public'.\DIRECTORY_SEPARATOR, '', $imagePath);

        return isset($basePath)
            ? rtrim($basePath, '/').'/'.ltrim($imagePath, '/')
            : '/'.ltrim($imagePath, '/');
    }
}
