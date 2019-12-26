<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\ImageProperty;

final class ImageConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof ImageProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $configuredBasePath = $propertyConfig->getCustomOption(ImageProperty::OPTION_BASE_PATH);
        $formattedValue = $this->getImagePath($propertyConfig->getValue(), $configuredBasePath);

        $propertyConfig->setFormattedValue($formattedValue);

        // this check is needed to avoid displaying broken images when image properties are optional
        if (empty($formattedValue) || $formattedValue === rtrim($configuredBasePath ?? '', '/')) {
            $propertyConfig->setTemplateName('label/empty');
        }
    }

    private function getImagePath(?string $imagePath, ?string $basePath): ?string
    {
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null === $imagePath || 0 !== preg_match('/^(http[s]?|\/\/)/i', $imagePath)) {
            return $imagePath;
        }

        return isset($basePath)
            ? rtrim($basePath, '/').'/'.ltrim($imagePath, '/')
            : '/'.ltrim($imagePath, '/');
    }
}
