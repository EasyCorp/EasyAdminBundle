<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Property\AssociationProperty;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssociationConfigurator implements PropertyConfiguratorInterface
{
    private $applicationContextProvider;
    private $entityFactory;
    private $crudUrlGenerator;
    private $translator;

    public function __construct(ApplicationContextProvider $applicationContextProvider, EntityFactory $entityFactory, CrudUrlGenerator $crudUrlGenerator, TranslatorInterface $translator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->entityFactory = $entityFactory;
        $this->crudUrlGenerator = $crudUrlGenerator;
        $this->translator = $translator;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof AssociationProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $propertyName = $propertyConfig->getName();
        if (!$entityDto->isAssociation($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property is not a Doctrine association, so it cannot be used as an association property.', $propertyName));
        }

        $targetEntityFqcn = $propertyConfig->getDoctrineMetadata()->get('targetEntity');
        $targetCrudControllerFqcn = $propertyConfig->getCustomOption(AssociationProperty::OPTION_CRUD_CONTROLLER)
            ?? $this->applicationContextProvider->getContext()->getCrudControllers()->getControllerFqcnByEntityFqcn($targetEntityFqcn);

        if (null === $targetCrudControllerFqcn) {
            throw new \RuntimeException(sprintf('It\'s not possible to find the CRUD controller associated to the "%s" entity of the "%s" property (which is a Doctrine association). Define the CRUD controller explicitly with the setCrudController() method on this property.', $targetEntityFqcn, $propertyConfig->getName()));
        }

        $propertyConfig->setCustomOption(AssociationProperty::OPTION_CRUD_CONTROLLER, $targetCrudControllerFqcn);

        if ($entityDto->isToOneAssociation($propertyName)) {
            $this->configureToOneAssociation($propertyConfig);
        }

        if ($entityDto->isToManyAssociation($propertyName)) {
            $this->configureToManyAssociation($propertyConfig);
        }

        if (true === $propertyConfig->getCustomOption(AssociationProperty::OPTION_AUTOCOMPLETE)) {
            // this enables autocompletion for compatible associations
            $propertyConfig->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

            $propertyConfig->setFormType(CrudAutocompleteType::class);
            $autocompleteEndpointUrl = $this->crudUrlGenerator
                ->buildForController($propertyConfig->getCustomOption(AssociationProperty::OPTION_CRUD_CONTROLLER))
                ->setAction('autocomplete')
                ->setEntityId(null)
                ->generateUrl();

            $propertyConfig->setFormTypeOption('attr.data-ea-autocomplete-endpoint-url', $autocompleteEndpointUrl);
        }
    }

    private function configureToOneAssociation(PropertyConfigInterface $propertyConfig): void
    {
        $propertyConfig->setCustomOption(AssociationProperty::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toOne');

        if (false === $propertyConfig->isRequired()) {
            $propertyConfig->setFormTypeOptionIfNotSet('placeholder', $this->translator->trans('label.form.empty_value', [], 'EasyAdminBundle'));
        }

        $targetEntityFqcn = $propertyConfig->getDoctrineMetadata()->get('targetEntity');
        $targetCrudControllerFqcn = $propertyConfig->getCustomOption(AssociationProperty::OPTION_CRUD_CONTROLLER);

        $targetEntityDto = null === $propertyConfig->getValue()
            ? $this->entityFactory->createForEntityFqcn($targetEntityFqcn)
            : $this->entityFactory->createForEntityInstance($propertyConfig->getValue());
        $propertyConfig->setFormTypeOptionIfNotSet('class', $targetEntityDto->getFqcn());

        $propertyConfig->setCustomOption(AssociationProperty::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($targetCrudControllerFqcn, $targetEntityDto));

        $propertyConfig->setFormattedValue($this->formatAsString($propertyConfig->getValue(), $targetEntityDto));
    }

    private function configureToManyAssociation(PropertyConfigInterface $propertyConfig): void
    {
        $propertyConfig->setCustomOption(AssociationProperty::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toMany');

        // associations different from *-to-one cannot be sorted
        $propertyConfig->setSortable(false);

        $propertyConfig->setFormTypeOptionIfNotSet('multiple', true);

        /** @var PersistentCollection $collection */
        $collection = $propertyConfig->getValue();
        $propertyConfig->setFormTypeOptionIfNotSet('class', $collection->getTypeClass()->getName());

        if (null === $propertyConfig->getTextAlign()) {
            $propertyConfig->setTextAlign('right');
        }

        $propertyConfig->setFormattedValue($this->countNumElements($collection));
    }

    private function formatAsString($entityInstance, EntityDto $entityDto): ?string
    {
        if (null === $entityInstance) {
            return null;
        }

        if (method_exists($entityInstance, '__toString')) {
            return (string) $entityInstance;
        }

        if (null !== $primaryKeyValue = $entityDto->getPrimaryKeyValue()) {
            return sprintf('%s #%s', $entityDto->getName(), $primaryKeyValue);
        }

        return $entityDto->getName();
    }

    private function generateLinkToAssociatedEntity(string $crudController, EntityDto $entityDto): ?string
    {
        // TODO: check if user has permission to see the related entity
        return $this->crudUrlGenerator->buildForController($crudController)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityDto->getPrimaryKeyValue())
            ->includeReferrer()
            ->generateUrl();
    }

    private function countNumElements($collection): int
    {
        if (null === $collection) {
            return 0;
        }

        if (\is_array($collection) || $collection instanceof \Countable) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }
}
