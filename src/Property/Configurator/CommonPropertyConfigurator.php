<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyDefinitionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CommonPropertyConfigurator implements PropertyConfiguratorInterface
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

    public function supports(PropertyConfigInterface $property, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(PropertyDto $propertyDto, EntityDto $entityDto): void
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $translationDomain = $applicationContext->getI18n()->getTranslationDomain();

        $value = $this->buildValueProperty($propertyDto, $entityDto);

        $propertyDto
            ->setValue($value)
            ->setFormattedValue($value)
            ->setLabel($this->buildLabelProperty($propertyDto, $translationDomain))
            ->setSortable($this->buildSortableProperty($propertyDto, $entityDto))
            ->setVirtual($this->buildVirtualProperty($propertyDto, $entityDto))
            ->setResolvedTemplatePath($this->buildTemplatePathProperty($applicationContext, $propertyDto, $entityDto, $value));

        if (null !== $propertyDto->getHelp()) {
            $propertyDto->setHelp($this->buildHelpProperty($propertyDto, $translationDomain));
        }
    }

    private function buildHelpProperty(PropertyDto $propertyDto, string $translationDomain): ?string
    {
        if ((null === $help = $propertyDto->getHelp()) || empty($help)) {
            return $help;
        }

        return $this->translator->trans($help, $propertyDto->getTranslationParams(), $translationDomain);
    }

    private function buildLabelProperty(PropertyDto $propertyDto, string $translationDomain): string
    {
        // it field doesn't define its label explicitly, generate an automatic
        // label based on the field's property name
        if (null === $label = $propertyDto->getLabel()) {
            $label = $this->humanizeString($propertyDto->getName());
        }

        if (empty($label)) {
            return $label;
        }

        return $this->translator->trans($label, $propertyDto->getTranslationParams(), $translationDomain);
    }

    private function buildSortableProperty(PropertyDto $propertyDto, EntityDto $entityDto): bool
    {
        if (null !== $isSortable = $propertyDto->isSortable()) {
            return $isSortable;
        }

        return $entityDto->hasProperty($propertyDto->getName());
    }

    private function buildVirtualProperty(PropertyDto $propertyDto, EntityDto $entityDto): bool
    {
        return !$entityDto->hasProperty($propertyDto->getName());
    }

    private function buildValueProperty(PropertyDto $propertyDto, EntityDto $entityDto)
    {
        $entityInstance = $entityDto->getInstance();
        $propertyName = $propertyDto->getName();

        if ($this->propertyAccessor->isReadable($entityInstance, $propertyName)) {
            return $this->propertyAccessor->getValue($entityInstance, $propertyName);
        }

        return null;
    }

    private function buildTemplatePathProperty(ApplicationContext $applicationContext, PropertyDto $propertyDto, EntityDto $entityDto, $propertyValue): string
    {
        if (null !== $templatePath = $propertyDto->getConfiguredTemplatePath()) {
            return $templatePath;
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $propertyDto->getName());
        if (!$isPropertyReadable) {
            return $applicationContext->getTemplatePath('label/inaccessible');
        }

        if (null === $propertyValue) {
            return $applicationContext->getTemplatePath('label/null');
        }

        // TODO: move this condition to each property class
        if (empty($propertyValue) && \in_array($propertyDto->getType(), ['image', 'file', 'array', 'simple_array'])) {
            return $applicationContext->getTemplatePath('label/empty');
        }

        if (null === $templateName = $propertyDto->getConfiguredTemplateName()) {
            throw new \RuntimeException(sprintf('Properties must define either their templateName or their templatePath. None give for "%s" property.', $propertyDto->getName()));
        }

        return $applicationContext->getTemplatePath($templateName);
    }

    // copied from Symfony\Component\Form\FormRenderer::humanize()
    // (author: Bernhard Schussek <bschussek@gmail.com>).
    private function humanizeString(string $string): string
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $string))));
    }
}
