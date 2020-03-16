<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextAreaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use function Symfony\Component\String\u;

final class TextConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof TextField || $field instanceof TextAreaField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        if ($field instanceof TextAreaField) {
            $field->setFormTypeOptionIfNotSet('attr.rows', $field->getCustomOption(TextAreaField::OPTION_NUM_OF_ROWS));
        }

        if (null === $field->getValue()) {
            return;
        }

        $configuredMaxLength = $field->getCustomOption(TextAreaField::OPTION_MAX_LENGTH);
        $defaultMaxLength = Action::DETAIL === $action ? PHP_INT_MAX : 64;
        $formattedValue = u($field->getValue())->truncate($configuredMaxLength ?? $defaultMaxLength, '…');

        $field->setFormattedValue($formattedValue);
    }
}
