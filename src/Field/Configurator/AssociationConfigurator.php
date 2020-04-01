<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssociationConfigurator implements FieldConfiguratorInterface
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

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return AssociationField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $propertyName = $field->getProperty();
        if (!$entityDto->isAssociation($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" field is not a Doctrine association, so it cannot be used as an association field.', $propertyName));
        }

        $targetEntityFqcn = $field->getDoctrineMetadata()->get('targetEntity');
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER)
            ?? $context->getCrudControllers()->getControllerFqcnByEntityFqcn($targetEntityFqcn);

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

    private function configureToOneAssociation(FieldDto $field): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toOne');

        if (false === $field->getFormTypeOption('required')) {
            $field->setFormTypeOptionIfNotSet('placeholder', $this->translator->trans('label.form.empty_value', [], 'EasyAdminBundle'));
        }

        $targetEntityFqcn = $field->getDoctrineMetadata()->get('targetEntity');
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER);

        $targetEntityDto = null === $field->getValue()
            ? $this->entityFactory->create($targetEntityFqcn)
            : $this->entityFactory->createForEntityInstance($field->getValue());
        $field->setFormTypeOptionIfNotSet('class', $targetEntityDto->getFqcn());

        $field->setCustomOption(AssociationField::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($targetCrudControllerFqcn, $targetEntityDto));

        $field->setFormattedValue($this->formatAsString($field->getValue(), $targetEntityDto));
    }

    private function configureToManyAssociation(FieldDto $field): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toMany');

        // associations different from *-to-one cannot be sorted
        $field->setSortable(false);

        $field->setFormTypeOptionIfNotSet('multiple', true);

        /* @var PersistentCollection $collection */
        $field->setFormTypeOptionIfNotSet('class', $field->getDoctrineMetadata()->get('targetEntity'));

        if (null === $field->getTextAlign()) {
            $field->setTextAlign('right');
        }

        $field->setFormattedValue($this->countNumElements($field->getValue()));
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
