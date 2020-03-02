<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Property\AssociationProperty;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssociationConfigurator implements PropertyConfiguratorInterface
{
    private $entityFactory;
    private $crudUrlGenerator;
    private $translator;

    public function __construct(EntityFactory $entityFactory, CrudUrlGenerator $crudUrlGenerator, TranslatorInterface $translator)
    {
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

        // this enables autocompletion for compatible associations
        $propertyConfig->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

        if ($entityDto->isToOneAssociation($propertyName)) {
            $this->configureToOneAssociation($propertyConfig);
        }

        if ($entityDto->isToManyAssociation($propertyName)) {
            $this->configureToManyAssociation($propertyConfig);
        }
    }

    private function configureToOneAssociation(PropertyConfigInterface $propertyConfig, string $associatedCrudControllerFqcn): void
    {
        // TODO: improve this to find the related Crud Controller automatically
        if (null === $associatedCrudController = $propertyConfig->getCustomOption(AssociationProperty::OPTION_CRUD_CONTROLLER)) {
            throw new \RuntimeException(sprintf('The "%s" property is a Doctrine association and it must define its related controller using the setCrudController() method.', $propertyConfig->getName()));
        }

        $propertyConfig->setCustomOption(AssociationProperty::OPTION_TYPE, 'toOne');

        if (false === $propertyConfig->isRequired()) {
            $propertyConfig->setFormTypeOptionIfNotSet('placeholder', $this->translator->trans('label.form.empty_value', [], 'EasyAdminBundle'));
        }

        $associatedEntityDto = $this->entityFactory->createForEntityInstance($propertyConfig->getValue());
        $propertyConfig->setFormTypeOptionIfNotSet('class', $associatedEntityDto->getFqcn());

        $propertyConfig->setCustomOption(AssociationProperty::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($associatedCrudControllerFqcn, $associatedEntityDto));

        $propertyConfig->setFormattedValue($this->formatAsString($propertyConfig->getValue(), $associatedEntityDto));
    }

    private function configureToManyAssociation(PropertyConfigInterface $propertyConfig): void
    {
        $propertyConfig->setCustomOption(AssociationProperty::OPTION_TYPE, 'toMany');

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
