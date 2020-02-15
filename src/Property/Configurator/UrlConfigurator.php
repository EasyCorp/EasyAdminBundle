<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\UrlProperty;
use function Symfony\Component\String\u;

final class UrlConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof UrlProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $formTypeOptions = $propertyConfig->getFormTypeOptions();
        $formTypeOptions['attr']['inputmode'] = $formTypeOptions['attr']['inputmode'] ?? 'url';
        $propertyConfig->setFormTypeOptions($formTypeOptions);

        $prettyUrl = str_replace(['http://www.', 'https://www.', 'http://', 'https://'], '', $propertyConfig->getValue());
        $prettyUrl = rtrim($prettyUrl, '/');

        if ('index' === $action) {
            $prettyUrl = u($prettyUrl)->truncate(32, '…');
        }

        $propertyConfig->setFormattedValue($prettyUrl);
    }
}
