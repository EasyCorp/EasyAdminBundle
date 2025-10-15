<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Adapter\LocalFileAdapter;
use EasyCorp\Bundle\EasyAdminBundle\Adapter\UploadedFileAdapterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

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
        $uploadedFileAdapter = $field->getCustomOption(ImageField::OPTION_UPLOADED_FILE_ADAPTER);

        if (null === $uploadedFileAdapter) {
            if (null === $field->getCustomOption(ImageField::OPTION_UPLOAD_DIR)) {
                throw new \InvalidArgumentException(sprintf('The "%s" image field must define the directory where the images are uploaded using the setUploadDir() method.', $field->getProperty()));
            }
            $uploadedFileAdapter = new LocalFileAdapter($field->getCustomOption(ImageField::OPTION_UPLOAD_DIR), $configuredBasePath, $this->projectDir);
        }
        $field->setFormTypeOption('uploaded_file_adapter', $uploadedFileAdapter);

        $formattedValue = \is_array($field->getValue())
            ? $this->getImagesPaths($field->getValue(), $uploadedFileAdapter)
            : $this->getImagePath($field->getValue(), $uploadedFileAdapter);
        $field->setFormattedValue($formattedValue);

        $field->setFormTypeOption('upload_filename', $field->getCustomOption(ImageField::OPTION_UPLOADED_FILE_NAME_PATTERN));

        // this check is needed to avoid displaying broken images when image properties are optional
        if (null === $formattedValue || '' === $formattedValue || (\is_array($formattedValue) && 0 === \count($formattedValue)) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $field->setTemplateName('label/empty');
        }

        if (!\in_array($context->getCrud()->getCurrentPage(), [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            return;
        }

        $field->setFormTypeOption('file_constraints', $field->getCustomOption(ImageField::OPTION_FILE_CONSTRAINTS));
    }

    /**
     * @param array<string|null>|null $images
     *
     * @return array<string|null>
     */
    private function getImagesPaths(?array $images, UploadedFileAdapterInterface $uploadedFileAdapter): array
    {
        $imagesPaths = [];
        foreach ($images as $image) {
            $imagesPaths[] = $this->getImagePath($image, $uploadedFileAdapter);
        }

        return $imagesPaths;
    }

    private function getImagePath(?string $imagePath, UploadedFileAdapterInterface $uploadedFileAdapter): ?string
    {
        if (null === $imagePath) {
            return null;
        }

        return $uploadedFileAdapter->publicUrl($imagePath);
    }
}
