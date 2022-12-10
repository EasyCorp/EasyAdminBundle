<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use function Symfony\Component\Translation\t;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SlugConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return SlugField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $targetFieldNames = (array) $field->getCustomOption(SlugField::OPTION_TARGET_FIELD_NAME);
        if ([] === $targetFieldNames) {
            throw new \RuntimeException(sprintf('The "%s" field must define the name(s) of the field(s) whose contents are used for the slug using the "setTargetFieldName()" method.', $field->getProperty()));
        }

        $field->setFormTypeOption('target', implode('|', $targetFieldNames));

        if (null !== $unlockConfirmationMessage = $field->getCustomOption(SlugField::OPTION_UNLOCK_CONFIRMATION_MESSAGE)) {
            if (!$unlockConfirmationMessage instanceof TranslatableInterface) {
                $unlockConfirmationMessage = t($unlockConfirmationMessage, [], $context->getI18n()->getTranslationDomain());
            }

            $field->setFormTypeOption('attr.data-confirm-text', $unlockConfirmationMessage);
        }
    }
}
