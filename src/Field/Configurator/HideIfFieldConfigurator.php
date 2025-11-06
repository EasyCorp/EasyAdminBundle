<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore

final class HideIfFieldConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return $field->getCustomOptions()->has('hideIf');
    }

    public function configure(FieldDto $field, EntityDto $entityDto, $context): void
    {
        $condition = $field->getCustomOption('hideIf');

        if (null === $condition) {
            return;
        }

        $shouldHide = false;

        if (is_bool($condition)) {
            $shouldHide = $condition;
        } elseif (is_callable($condition)) {
            $entityInstance = $entityDto->getInstance();
            $shouldHide = (bool) $condition($entityInstance);
        }

        if ($shouldHide) {
            $field->setDisplayedOn(KeyValueStore::new([]));
        }
    }
}
