<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;

class EmbeddedFilterConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return $entityDto->isEmbeddedClassProperty($filterDto->getProperty());
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $filterDto->setProperty(str_replace('.', '_', $filterDto->getProperty()));
        $propertyPath = $filterDto->getFormTypeOption('property_path');

        if (!$propertyPath) {
            // The property accessor sets values on array.
            // So we must replace object path to array path.
            $paths = explode('.', $filterDto->getProperty());
            foreach ($paths as $key => $path) {
                $paths[$key] = "[$path]";
            }

            // We set the property path as form option
            $filterDto->setFormTypeOption('property_path', implode('', $paths));
        }
    }
}