<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\TextEditorProperty;

final class TextEditorConfigurator implements PropertyConfiguratorInterface
{
    private $adminContextProvider;

    public function __construct(AdminContextProvider $adminContextProvider)
    {
        $this->adminContextProvider = $adminContextProvider;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof TextEditorProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if ('rtl' === $this->adminContextProvider->getContext()->getI18n()->getTextDirection()) {
            $propertyConfig->addCssFiles('bundles/easyadmin/form-type-text-editor.rtl.css');
        }
    }
}
