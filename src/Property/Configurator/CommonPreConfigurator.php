<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CommonPreConfigurator implements PropertyConfiguratorInterface
{
    private $applicationContextProvider;
    private $translator;
    private $propertyAccessor;

    public function __construct(ApplicationContextProvider $applicationContextProvider, TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $translationDomain = $applicationContext->getI18n()->getTranslationDomain();

        $value = $this->buildValueOption($action, $propertyConfig, $entityDto);
        $label = $this->buildLabelOption($propertyConfig, $translationDomain);
        $isRequired = $this->buildRequiredOption($propertyConfig, $entityDto);
        $isSortable = $this->buildSortableOption($propertyConfig, $entityDto);
        $isVirtual = $this->buildVirtualOption($propertyConfig, $entityDto);
        $templatePath = $this->buildTemplatePathOption($applicationContext, $propertyConfig, $entityDto, $value);
        $doctrineMetadata = $entityDto->hasProperty($propertyConfig->getName()) ? $entityDto->getPropertyMetadata($propertyConfig->getName()) : [];

        $propertyConfig
            ->setValue($value)
            ->setFormattedValue($value)
            ->setLabel($label)
            ->setRequired($isRequired)
            ->setSortable($isSortable)
            ->setVirtual($isVirtual)
            ->setTemplatePath($templatePath)
            ->setDoctrineMetadata($doctrineMetadata);

        if (null !== $propertyConfig->getHelp()) {
            $helpMessage = $this->buildHelpOption($propertyConfig, $translationDomain);
            $propertyConfig->setHelp($helpMessage);

            $propertyConfig->setFormTypeOptionIfNotSet('help', $helpMessage);
            $propertyConfig->setFormTypeOptionIfNotSet('help_html', true);
            $propertyConfig->setFormTypeOptionIfNotSet('help_translation_parameters', $propertyConfig->getTranslationParameters());
        }

        if (null !== $propertyConfig->getCssClass()) {
            $propertyConfig->setFormTypeOptionIfNotSet('row_attr.class', $propertyConfig->getCssClass());
        }

        if (null !== $propertyConfig->getTextAlign()) {
            $propertyConfig->setFormTypeOptionIfNotSet('attr.align', $propertyConfig->getTextAlign());
        }

        $propertyConfig->setFormTypeOptionIfNotSet('label', $propertyConfig->getLabel());
        $propertyConfig->setFormTypeOptionIfNotSet('label_translation_parameters', $propertyConfig->getTranslationParameters());
    }

    private function buildValueOption(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto)
    {
        $entityInstance = $entityDto->getInstance();
        $propertyName = $propertyConfig->getName();

        if (!$this->propertyAccessor->isReadable($entityInstance, $propertyName)) {
            return null;
        }

        return $this->propertyAccessor->getValue($entityInstance, $propertyName);
    }

    private function buildHelpOption(PropertyConfigInterface $propertyConfig, string $translationDomain): ?string
    {
        if ((null === $help = $propertyConfig->getHelp()) || empty($help)) {
            return $help;
        }

        return $this->translator->trans($help, $propertyConfig->getTranslationParameters(), $translationDomain);
    }

    private function buildLabelOption(PropertyConfigInterface $propertyConfig, string $translationDomain): string
    {
        // it field doesn't define its label explicitly, generate an automatic
        // label based on the field's property name
        if (null === $label = $propertyConfig->getLabel()) {
            $label = $this->humanizeString($propertyConfig->getName());
        }

        if (empty($label)) {
            return $label;
        }

        return $this->translator->trans($label, $propertyConfig->getTranslationParameters(), $translationDomain);
    }

    private function buildSortableOption(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        if (null !== $isSortable = $propertyConfig->isSortable()) {
            return $isSortable;
        }

        return $entityDto->hasProperty($propertyConfig->getName());
    }

    private function buildVirtualOption(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return !$entityDto->hasProperty($propertyConfig->getName());
    }

    private function buildTemplatePathOption(ApplicationContext $applicationContext, PropertyConfigInterface $propertyConfig, EntityDto $entityDto, $propertyValue): string
    {
        if (null !== $templatePath = $propertyConfig->getTemplatePath()) {
            return $templatePath;
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $propertyConfig->getName());
        if (!$isPropertyReadable) {
            return $applicationContext->getTemplatePath('label/inaccessible');
        }

        if (null === $propertyValue && 'boolean' !== $propertyConfig->getType()) {
            return $applicationContext->getTemplatePath('label/null');
        }

        // TODO: move this condition to each property class
        if (empty($propertyValue) && \in_array($propertyConfig->getType(), ['image', 'file', 'array', 'simple_array'])) {
            return $applicationContext->getTemplatePath('label/empty');
        }

        if (null === $templateName = $propertyConfig->getTemplateName()) {
            throw new \RuntimeException(sprintf('Properties must define either their templateName or their templatePath. None give for "%s" property.', $propertyConfig->getName()));
        }

        return $applicationContext->getTemplatePath($templateName);
    }

    private function buildRequiredOption(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        if (null !== $isRequired = $propertyConfig->isRequired()) {
            return $isRequired;
        }

        // consider that virtual properties are not required
        if (!$entityDto->hasProperty($propertyConfig->getName())) {
            return false;
        }

        // TODO: fix this and see if there's any way to check if an association is nullable
        if ($entityDto->isAssociation($propertyConfig->getName())) {
            return false;
        }

        $doctrinePropertyMetadata = $entityDto->getPropertyMetadata($propertyConfig->getName());

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
