<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AbstractFileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FileConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return \in_array($field->getFieldFqcn(), [
            ImageField::class,
            FileField::class,
        ], true);
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $configuredBasePath = $field->getCustomOption(AbstractFileField::OPTION_BASE_PATH);
        $formattedValue = $this->getFilePath($field->getValue(), $configuredBasePath);

        $field->setFormattedValue($formattedValue);

        // this check is needed to avoid displaying broken images when image properties are optional
        if (empty($formattedValue) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $field->setTemplateName('label/empty');
        }
    }

    private function getFilePath(?string $filePath, ?string $basePath): ?string
    {
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null === $filePath || 0 !== preg_match('/^(http[s]?|\/\/)/i', $filePath)) {
            return $filePath;
        }

        return isset($basePath)
            ? rtrim($basePath, '/').'/'.ltrim($filePath, '/')
            : '/'.ltrim($filePath, '/');
    }
}
