<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

final class BooleanConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof BooleanField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        // TODO: ask someone who knows Symfony forms well how to make this work
        if ($field->getCustomOption(BooleanField::OPTION_RENDER_AS_SWITCH)) {
            // see https://symfony.com/blog/new-in-symfony-4-4-bootstrap-custom-switches
            // $field->setFormTypeOptionIfNotSet('label_attr.class', 'switch-custom');
        }
    }
}
