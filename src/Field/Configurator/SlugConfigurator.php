<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SlugConfigurator implements FieldConfiguratorInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return SlugField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $targetFieldName = $field->getCustomOption(SlugField::OPTION_TARGET_FIELD_NAME)) {
            throw new \RuntimeException(sprintf('The "%s" field must define the name of the field whose contents are used for the slug using the "setTargetFieldName()" method.', $field->getProperty()));
        }

        $field->setFormTypeOption('target', $targetFieldName);

        if (null !== $unlockConfirmationMessage = $field->getCustomOption(SlugField::OPTION_UNLOCK_CONFIRMATION_MESSAGE)) {
            $field->setFormTypeOption('attr.data-confirm-text', $this->translator->trans($unlockConfirmationMessage, [], $context->getI18n()->getTranslationDomain()));
        }
    }
}
