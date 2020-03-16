<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;

final class EmailConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof EmailField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        $formTypeOptions = $field->getFormTypeOptions();
        $formTypeOptions['attr']['inputmode'] = $formTypeOptions['attr']['inputmode'] ?? 'email';

        $field->setFormTypeOptions($formTypeOptions);
    }
}
