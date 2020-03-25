<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormPanelField;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CommonPreConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;
    private $translator;
    private $propertyAccessor;
    private static $numOfSpecialFormProperties = 0;

    public function __construct(AdminContextProvider $adminContextProvider, TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        if ($field instanceof FormPanelField) {
            $field->setProperty('ea_form_panel_'.self::$numOfSpecialFormProperties++);
        }

        $adminContext = $this->adminContextProvider->getContext();
        $translationDomain = $adminContext->getI18n()->getTranslationDomain();

        $value = $this->buildValueOption($field, $entityDto);
        $label = $this->buildLabelOption($field, $translationDomain);
        $isRequired = $this->buildRequiredOption($field, $entityDto);
        $isSortable = $this->buildSortableOption($field, $entityDto);
        $isVirtual = $this->buildVirtualOption($field, $entityDto);
        $templatePath = $this->buildTemplatePathOption($adminContext, $field, $entityDto, $value);
        $doctrineMetadata = $entityDto->hasProperty($field->getProperty()) ? $entityDto->getPropertyMetadata($field->getProperty()) : [];

        $field
            ->setValue($value)
            ->setFormattedValue($value)
            ->setLabel($label)
            ->setRequired($isRequired)
            ->setSortable($isSortable)
            ->setVirtual($isVirtual)
            ->setTemplatePath($templatePath)
            ->setDoctrineMetadata($doctrineMetadata);

        if (null !== $field->getHelp()) {
            $helpMessage = $this->buildHelpOption($field, $translationDomain);
            $field->setHelp($helpMessage);

            $field->setFormTypeOptionIfNotSet('help', $helpMessage);
            $field->setFormTypeOptionIfNotSet('help_html', true);
            $field->setFormTypeOptionIfNotSet('help_translation_parameters', $field->getTranslationParameters());
        }

        if (null !== $field->getCssClass()) {
            $field->setFormTypeOptionIfNotSet('row_attr.class', $field->getCssClass());
        }

        if (null !== $field->getTextAlign()) {
            $field->setFormTypeOptionIfNotSet('attr.align', $field->getTextAlign());
        }

        $field->setFormTypeOptionIfNotSet('label', $field->getLabel());
        $field->setFormTypeOptionIfNotSet('label_translation_parameters', $field->getTranslationParameters());
    }

    private function buildValueOption(FieldInterface $field, EntityDto $entityDto)
    {
        $entityInstance = $entityDto->getInstance();
        $propertyName = $field->getProperty();

        if (!$this->propertyAccessor->isReadable($entityInstance, $propertyName)) {
            return null;
        }

        return $this->propertyAccessor->getValue($entityInstance, $propertyName);
    }

    private function buildHelpOption(FieldInterface $field, string $translationDomain): ?string
    {
        if ((null === $help = $field->getHelp()) || empty($help)) {
            return $help;
        }

        return $this->translator->trans($help, $field->getTranslationParameters(), $translationDomain);
    }

    private function buildLabelOption(FieldInterface $field, string $translationDomain): string
    {
        // it field doesn't define its label explicitly, generate an automatic
        // label based on the field's field name
        if (null === $label = $field->getLabel()) {
            $label = $this->humanizeString($field->getProperty());
        }

        if (empty($label)) {
            return $label;
        }

        return $this->translator->trans($label, $field->getTranslationParameters(), $translationDomain);
    }

    private function buildSortableOption(FieldInterface $field, EntityDto $entityDto): bool
    {
        if (null !== $isSortable = $field->isSortable()) {
            return $isSortable;
        }

        return $entityDto->hasProperty($field->getProperty());
    }

    private function buildVirtualOption(FieldInterface $field, EntityDto $entityDto): bool
    {
        return !$entityDto->hasProperty($field->getProperty());
    }

    private function buildTemplatePathOption(AdminContext $adminContext, FieldInterface $field, EntityDto $entityDto, $fieldValue): string
    {
        if (null !== $templatePath = $field->getTemplatePath()) {
            return $templatePath;
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $field->getProperty());
        if (!$isPropertyReadable) {
            return $adminContext->getTemplatePath('label/inaccessible');
        }

        if (null === $fieldValue && 'boolean' !== $field->getType()) {
            return $adminContext->getTemplatePath('label/null');
        }

        // TODO: move this condition to each field class
        if (empty($fieldValue) && \in_array($field->getType(), ['image', 'file', 'array', 'simple_array'])) {
            return $adminContext->getTemplatePath('label/empty');
        }

        if (null === $templateName = $field->getTemplateName()) {
            throw new \RuntimeException(sprintf('Fields must define either their templateName or their templatePath. None give for "%s" field.', $field->getProperty()));
        }

        return $adminContext->getTemplatePath($templateName);
    }

    private function buildRequiredOption(FieldInterface $field, EntityDto $entityDto): bool
    {
        if (null !== $isRequired = $field->isRequired()) {
            return $isRequired;
        }

        // consider that virtual properties are not required
        if (!$entityDto->hasProperty($field->getProperty())) {
            return false;
        }

        // TODO: fix this and see if there's any way to check if an association is nullable
        if ($entityDto->isAssociation($field->getProperty())) {
            return false;
        }

        $doctrinePropertyMetadata = $entityDto->getPropertyMetadata($field->getProperty());

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
