<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CommonPreConfigurator implements FieldConfiguratorInterface
{
    private $translator;
    private $propertyAccessor;

    public function __construct(TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $translationDomain = $context->getI18n()->getTranslationDomain();

        $value = $this->buildValueOption($field, $entityDto);
        $field->setValue($value);
        $field->setFormattedValue($value);

        $label = $this->buildLabelOption($field, $translationDomain);
        $field->setLabel($label);

        $isRequired = $this->buildRequiredOption($field, $entityDto);
        $field->setFormTypeOption('required', $isRequired);

        $isSortable = $this->buildSortableOption($field, $entityDto);
        $field->setSortable($isSortable);

        $isVirtual = $this->buildVirtualOption($field, $entityDto);
        $field->setVirtual($isVirtual);

        $templatePath = $this->buildTemplatePathOption($context, $field, $entityDto, $value);
        $field->setTemplatePath($templatePath);

        $doctrineMetadata = $entityDto->hasProperty($field->getName()) ? $entityDto->getPropertyMetadata($field->getName()) : [];
        $field->setDoctrineMetadata($doctrineMetadata);

        if (null !== $field->getHelp()) {
            $helpMessage = $this->buildHelpOption($field, $translationDomain);
            $field->setHelp($helpMessage);

            $field->setFormTypeOptionIfNotSet('help', $helpMessage);
            $field->setFormTypeOptionIfNotSet('help_html', true);
            $field->setFormTypeOptionIfNotSet('help_translation_parameters', $field->getTranslationParameters());
        }

        if (!empty($field->getCssClass())) {
            $field->setFormTypeOptionIfNotSet('row_attr.class', $field->getCssClass());
        }

        if (null !== $field->getTextAlign()) {
            $field->setFormTypeOptionIfNotSet('attr.align', $field->getTextAlign());
        }

        $field->setFormTypeOptionIfNotSet('label', $field->getLabel());
        $field->setFormTypeOptionIfNotSet('label_translation_parameters', $field->getTranslationParameters());
    }

    private function buildValueOption(FieldDto $field, EntityDto $entityDto)
    {
        $entityInstance = $entityDto->getInstance();
        $propertyName = $field->getName();

        if (!$this->propertyAccessor->isReadable($entityInstance, $propertyName)) {
            return null;
        }

        return $this->propertyAccessor->getValue($entityInstance, $propertyName);
    }

    private function buildHelpOption(FieldDto $field, string $translationDomain): ?string
    {
        if ((null === $help = $field->getHelp()) || empty($help)) {
            return $help;
        }

        return $this->translator->trans($help, $field->getTranslationParameters(), $translationDomain);
    }

    private function buildLabelOption(FieldDto $field, string $translationDomain): ?string
    {
        // don't autogenerate a label for these special fields (there's a dedicated configurator for them)
        if (FormField::class === $field->getFieldFqcn()) {
            return $field->getLabel();
        }

        // it field doesn't define its label explicitly, generate an automatic
        // label based on the field's field name
        if (null === $label = $field->getLabel()) {
            $label = $this->humanizeString($field->getName());
        }

        if (empty($label)) {
            return $label;
        }

        return $this->translator->trans($label, $field->getTranslationParameters(), $translationDomain);
    }

    private function buildSortableOption(FieldDto $field, EntityDto $entityDto): bool
    {
        if (null !== $isSortable = $field->isSortable()) {
            return $isSortable;
        }

        return $entityDto->hasProperty($field->getName());
    }

    private function buildVirtualOption(FieldDto $field, EntityDto $entityDto): bool
    {
        return !$entityDto->hasProperty($field->getName());
    }

    private function buildTemplatePathOption(AdminContext $adminContext, FieldDto $field, EntityDto $entityDto, $fieldValue): string
    {
        if (null !== $templatePath = $field->getTemplatePath()) {
            return $templatePath;
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $field->getName());
        if (!$isPropertyReadable) {
            return $adminContext->getTemplatePath('label/inaccessible');
        }

        if (null === $fieldValue && BooleanField::class !== $field->getFieldFqcn()) {
            return $adminContext->getTemplatePath('label/null');
        }

        // TODO: move this condition to each field class
        if (empty($fieldValue) && \in_array($field->getFieldFqcn(), [ImageField::class/*'file',*/ /*'array',*/ /*'simple_array' */], true)) {
            return $adminContext->getTemplatePath('label/empty');
        }

        if (null === $templateName = $field->getTemplateName()) {
            throw new \RuntimeException(sprintf('Fields must define either their templateName or their templatePath. None given for "%s" field.', $field->getName()));
        }

        return $adminContext->getTemplatePath($templateName);
    }

    private function buildRequiredOption(FieldDto $field, EntityDto $entityDto): bool
    {
        if (null !== $isRequired = $field->getFormTypeOption('required')) {
            return $isRequired;
        }

        // consider that virtual properties are not required
        if (!$entityDto->hasProperty($field->getName())) {
            return false;
        }

        // TODO: fix this and see if there's any way to check if an association is nullable
        if ($entityDto->isAssociation($field->getName())) {
            return false;
        }

        $doctrinePropertyMetadata = $entityDto->getPropertyMetadata($field->getName());

        // TODO: check if it's correct to never make a boolean value required
        // I guess it's correct because Symfony Forms treat NULL as FALSE by default (i.e. in the database the value won't be NULL)
        if ('boolean' === $doctrinePropertyMetadata['type']) {
            return false;
        }

        return !$doctrinePropertyMetadata['nullable'];
    }

    // copied from Symfony\Component\Form\FormRenderer::humanize()
    // (author: Bernhard Schussek <bschussek@gmail.com>).
    private function humanizeString(string $string): string
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $string))));
    }
}
