<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;

final class TimezoneConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof TimezoneField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');
        $field->setFormTypeOptionIfNotSet('intl', true);
    }
}
