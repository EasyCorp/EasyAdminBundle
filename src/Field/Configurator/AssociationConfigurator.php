<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssociationConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;
    private $entityFactory;
    private $crudUrlGenerator;
    private $translator;

    public function __construct(AdminContextProvider $adminContextProvider, EntityFactory $entityFactory, CrudUrlGenerator $crudUrlGenerator, TranslatorInterface $translator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->entityFactory = $entityFactory;
        $this->crudUrlGenerator = $crudUrlGenerator;
        $this->translator = $translator;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof AssociationField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        $propertyName = $field->getProperty();
        if (!$entityDto->isAssociation($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" field is not a Doctrine association, so it cannot be used as an association field.', $propertyName));
        }

        $targetEntityFqcn = $field->getDoctrineMetadata()->get('targetEntity');
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER)
            ?? $this->adminContextProvider->getContext()->getCrudControllers()->getControllerFqcnByEntityFqcn($targetEntityFqcn);

        if (null === $targetCrudControllerFqcn) {
            throw new \RuntimeException(sprintf('It\'s not possible to find the CRUD controller associated to the "%s" field of the "%s" entity. Define the associated CRUD controller explicitly with the setCrudController() method on this field.', $field->getProperty(), $targetEntityFqcn));
        }

        $field->setCustomOption(AssociationField::OPTION_CRUD_CONTROLLER, $targetCrudControllerFqcn);

        if ($entityDto->isToOneAssociation($propertyName)) {
            $this->configureToOneAssociation($field);
        }

        if ($entityDto->isToManyAssociation($propertyName)) {
            $this->configureToManyAssociation($field);
        }

        if (true === $field->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE)) {
            // this enables autocompletion for compatible associations
            $field->setFormTypeOptionIfNotSet('attr.data-widget', 'select2');

            $field->setFormType(CrudAutocompleteType::class);
            $autocompleteEndpointUrl = $this->crudUrlGenerator
                ->buildForController($field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER))
                ->setAction('autocomplete')
                ->setEntityId(null)
                ->generateUrl();

            $field->setFormTypeOption('attr.data-ea-autocomplete-endpoint-url', $autocompleteEndpointUrl);
        }
    }

    private function configureToOneAssociation(FieldInterface $field): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toOne');

        if (false === $field->isRequired()) {
            $field->setFormTypeOptionIfNotSet('placeholder', $this->translator->trans('label.form.empty_value', [], 'EasyAdminBundle'));
        }

        $targetEntityFqcn = $field->getDoctrineMetadata()->get('targetEntity');
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER);

        $targetEntityDto = null === $field->getValue()
            ? $this->entityFactory->createForEntityFqcn($targetEntityFqcn)
            : $this->entityFactory->createForEntityInstance($field->getValue());
        $field->setFormTypeOptionIfNotSet('class', $targetEntityDto->getFqcn());

        $field->setCustomOption(AssociationField::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($targetCrudControllerFqcn, $targetEntityDto));

        $field->setFormattedValue($this->formatAsString($field->getValue(), $targetEntityDto));
    }

    private function configureToManyAssociation(FieldInterface $field): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toMany');

        // associations different from *-to-one cannot be sorted
        $field->setSortable(false);

        $field->setFormTypeOptionIfNotSet('multiple', true);

        /** @var PersistentCollection $collection */
        $collection = $field->getValue();
        $field->setFormTypeOptionIfNotSet('class', $collection->getTypeClass()->getName());

        if (null === $field->getTextAlign()) {
            $field->setTextAlign('right');
        }

        $field->setFormattedValue($this->countNumElements($collection));
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
