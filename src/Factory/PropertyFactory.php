<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PropertyFactory
{
    private $applicationContextProvider;
    private $authorizationChecker;
    private $translator;
    private $propertyAccessor;

    public function __construct(ApplicationContextProvider $applicationContextProvider, AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param PropertyInterface[] $propertiesConfig
     */
    public function create(EntityDto $entityDto, iterable $propertiesConfig): EntityDto
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $translationDomain = $applicationContext->getI18n()->getTranslationDomain();

        $builtProperties = [];
        foreach ($propertiesConfig as $propertyConfig) {
            $propertyDto = $propertyConfig->getAsDto();
            if (false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_PROPERTY, $propertyDto)) {
                continue;
            }

            $value = $this->buildValueProperty($propertyDto, $entityDto);

            $propertyDto = $propertyDto->with([
                'formattedValue' => $value,
                'help' => $this->buildHelpProperty($propertyDto, $translationDomain),
                'label' => $this->buildLabelProperty($propertyDto, $translationDomain),
                'sortable' => $this->buildSortableProperty($propertyDto, $entityDto),
                'resolvedTemplatePath' => $this->buildTemplatePathProperty($applicationContext, $propertyDto, $entityDto, $value),
                'value' => $value,
                'virtual' => $this->buildVirtualProperty($propertyDto, $entityDto),
            ]);

            $propertyDto = $propertyConfig->build($propertyDto, $entityDto, $applicationContext);

            $builtProperties[] = $propertyDto;
        }

        return $entityDto->with([
            'properties' => PropertyDtoCollection::new($builtProperties),
        ]);
    }

    /**
     * @param PropertyInterface[] $propertiesConfig
     */
    public function createAll(EntityDto $entityDto, iterable $entityInstances, iterable $propertiesConfig): EntityDtoCollection
    {
        $builtEntities = [];
        foreach ($entityInstances as $entityInstance) {
            $currentEntityDto = $entityDto->with(['instance' => $entityInstance]);
            $builtEntities[] = $this->create($currentEntityDto, $propertiesConfig);
        }

        return EntityDtoCollection::new($builtEntities);
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
        if (null !== $templatePath = $propertyDto->get('templatePath')) {
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

        if (null === $templateName = $propertyDto->get('templateName')) {
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
