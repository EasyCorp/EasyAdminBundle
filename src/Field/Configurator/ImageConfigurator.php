<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ImageConfigurator implements FieldConfiguratorInterface
{
    private $projectDir;

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

        $formattedValue = \is_array($field->getValue())
            ? $this->getImagesPaths($field->getValue(), $configuredBasePath)
            : $this->getImagePath($field->getValue(), $configuredBasePath);
        $field->setFormattedValue($formattedValue);

        $field->setFormTypeOption('upload_filename', $field->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));

        // this check is needed to avoid displaying broken images when image properties are optional
        if (empty($formattedValue) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $field->setTemplateName('label/empty');
        }

        if (!\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            return;
        }

        $relativeUploadDir = $field->getCustomOption(ImageField::OPTION_UPLOAD_DIR);
        if (null === $relativeUploadDir) {
            throw new \InvalidArgumentException(sprintf('The "%s" image field must define the directory where the images are uploaded using the setUploadDir() method.', $field->getProperty()));
        }
        $relativeUploadDir = u($relativeUploadDir)->trimStart(\DIRECTORY_SEPARATOR)->ensureEnd(\DIRECTORY_SEPARATOR)->toString();
        $absoluteUploadDir = u($relativeUploadDir)->ensureStart($this->projectDir.\DIRECTORY_SEPARATOR)->toString();
        $field->setFormTypeOption('upload_dir', $absoluteUploadDir);
    }

    private function getImagesPaths(?array $images, ?string $basePath): array
    {
        $imagesPaths = [];
        foreach ($images as $image) {
            $imagesPaths[] = $this->getImagePath($image, $basePath);
        }

        return $imagesPaths;
    }

    private function getImagePath(?string $imagePath, ?string $basePath): ?string
    {
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
