<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Builder\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PropertyBuilder implements ItemCollectionBuilderInterface
{
    private $applicationContextProvider;
    private $authorizationChecker;
    private $translator;
    private $propertyAccessor;
    private $isBuilt;
    /** @var PropertyDtoCollection */
    private $properties;
    /** @var PropertyDtoCollection */
    private $builtProperties;

    public function __construct(ApplicationContextProvider $applicationContextProvider, AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param PropertyInterface $property
     */
    public function addItem($property): ItemCollectionBuilderInterface
    {
        $this->properties[] = $property;
        $this->resetBuiltProperties();

        return $this;
    }

    /**
     * @param PropertyDtoCollection[] $propertiesDto
     */
    public function setItems(array $propertiesDto): ItemCollectionBuilderInterface
    {
        $this->properties = $propertiesDto;
        $this->resetBuiltProperties();

        return $this;
    }

    /**
     * @return MenuItemDto[]
     */
    public function build(): PropertyDtoCollection
    {
        if (!$this->isBuilt) {
            $this->buildProperties();
            $this->isBuilt = true;
        }

        return $this->builtProperties;
    }

    public function buildForEntity(EntityDto $entityDto): PropertyDtoCollection
    {
        $this->build();

        $applicationContext = $this->applicationContextProvider->getContext();

        $updatedPropertiesDto = [];
        foreach ($this->builtProperties as $propertyDto) {
            // property is built in two steps because some dynamic properties depend on
            // other properties built dynamically in step 1
            $updatedPropertyDto = $propertyDto->with([
                'sortable' => $this->buildSortableProperty($propertyDto, $entityDto),
                'value' => $this->buildValueProperty($propertyDto, $entityDto),
                'virtual' => $this->buildVirtualProperty($propertyDto, $entityDto),
            ]);

            $updatedPropertyDto = $updatedPropertyDto->with([
                'templatePath' => $this->buildTemplatePathProperty($applicationContext, $updatedPropertyDto, $entityDto),
            ]);

            $updatedPropertiesDto[] = $updatedPropertyDto;
        }

        return PropertyDtoCollection::new($updatedPropertiesDto);
    }

    private function resetBuiltProperties(): void
    {
        $this->builtProperties = PropertyDtoCollection::new();
        $this->isBuilt = false;
    }

    private function buildProperties(): void
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $translationDomain = $applicationContext->getI18n()->getTranslationDomain();

        $builtProperties = [];
        foreach ($this->properties as $propertyDto) {
            if (false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_PROPERTY, $propertyDto)) {
                continue;
            }

            $builtProperties[] = $propertyDto->with([
                'help' => $this->buildHelpProperty($propertyDto, $translationDomain),
                'label' => $this->buildLabelProperty($propertyDto, $translationDomain),
            ]);
        }

        $this->builtProperties = PropertyDtoCollection::new($builtProperties);
    }

    public function buildForMultipleEntities(EntityDtoCollection $entitiesDto): EntityDtoCollection
    {
        $updatedEntitiesDto = [];
        foreach ($entitiesDto as $entityDto) {
            $updatedEntitiesDto[] = $this->buildForEntity($entityDto);
        }

        return EntityDtoCollection::new($updatedEntitiesDto);
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

    private function buildTemplatePathProperty(ApplicationContext $applicationContext, PropertyDto $propertyDto, EntityDto $entityDto): string
    {
        if (null !== $customTemplatePath = $propertyDto->getCustomTemplatePath()) {
            return $customTemplatePath;
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $propertyDto->getName());
        if (!$isPropertyReadable) {
            return $applicationContext->getTemplatePath('label_inaccessible');
        }

        if (null === $value = $propertyDto->getValue()) {
            return $applicationContext->getTemplatePath('label_null');
        }

        if (empty($value) && \in_array($propertyDto->getType(), ['image', 'file', 'array', 'simple_array'])) {
            return $applicationContext->getTemplatePath('label_empty');
        }

        return $propertyDto->getDefaultTemplatePath();
    }

    // copied from Symfony\Component\Form\FormRenderer::humanize()
    // (author: Bernhard Schussek <bschussek@gmail.com>).
    private function humanizeString(string $string): string
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $string))));
    }
}
