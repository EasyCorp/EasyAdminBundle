<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TextConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return \in_array($field->getFieldFqcn(), [TextField::class, TextareaField::class], true);
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if ($field instanceof TextareaField) {
            $field->setFormTypeOptionIfNotSet('attr.rows', $field->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
        }

        if (null === $field->getValue()) {
            return;
        }

        $configuredMaxLength = $field->getCustomOption(TextareaField::OPTION_MAX_LENGTH);
        $isDetailAction = Action::DETAIL === $context->getCrud()->getCurrentAction();
        $defaultMaxLength = $isDetailAction ? PHP_INT_MAX : 64;
        $formattedValue = u($field->getValue())->truncate($configuredMaxLength ?? $defaultMaxLength, '…');

        $field->setFormattedValue($formattedValue);
    }
}
