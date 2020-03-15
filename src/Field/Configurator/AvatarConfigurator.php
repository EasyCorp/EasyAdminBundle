<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;

final class AvatarConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof AvatarField;
    }

    public function configure(string $action, FieldInterface $field, EntityDto $entityDto): void
    {
        if (Action::INDEX === $action) {
            $field->setLabel(null);
        }

        if (null === $field->getCustomOption(AvatarField::OPTION_HEIGHT)) {
            $field->setCustomOption(AvatarField::OPTION_HEIGHT, Action::DETAIL === $action ? 48 : 28);
        }

        if ($field->getCustomOption(AvatarField::OPTION_IS_GRAVATAR_EMAIL)) {
            $field->setFormattedValue('https://www.gravatar.com/avatar/%s?s=%d&d=mp', md5($field->getValue()));
        }

        if (null === $field->getFormattedValue()) {
            $field->setTemplateName('label/null');
        }
    }
}
