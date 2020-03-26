<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;

final class AvatarConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return AvatarField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (Action::INDEX === $context->getCrud()->getCurrentAction()) {
            $field->setLabel(null);
        }

        if (null === $field->getCustomOption(AvatarField::OPTION_HEIGHT)) {
            $isDetailAction = Action::DETAIL === $context->getCrud()->getCurrentAction();
            $field->setCustomOption(AvatarField::OPTION_HEIGHT, $isDetailAction ? 48 : 28);
        }

        if ($field->getCustomOption(AvatarField::OPTION_IS_GRAVATAR_EMAIL)) {
            $field->setFormattedValue(sprintf('https://www.gravatar.com/avatar/%s?s=%d&d=mp', md5($field->getValue()), $field->getCustomOption(AvatarField::OPTION_HEIGHT)));
        }

        if (null === $field->getFormattedValue()) {
            $field->setTemplateName('label/null');
        }
    }
}
