<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AssociationConfigurator implements FieldConfiguratorInterface
{
    private $entityFactory;
    private $adminUrlGenerator;
    private $translator;

    public function __construct(EntityFactory $entityFactory, AdminUrlGenerator $adminUrlGenerator, TranslatorInterface $translator)
    {
        $this->entityFactory = $entityFactory;
        $this->adminUrlGenerator = $adminUrlGenerator;
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
        // the target CRUD controller can be NULL; in that case, field value doesn't link to the related entity
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER)
            ?? $context->getCrudControllers()->findCrudFqcnByEntityFqcn($targetEntityFqcn);
        $field->setCustomOption(AssociationField::OPTION_CRUD_CONTROLLER, $targetCrudControllerFqcn);

        if (AssociationField::WIDGET_AUTOCOMPLETE === $field->getCustomOption(AssociationField::OPTION_WIDGET)) {
            $field->setFormTypeOption('attr.data-ea-widget', 'ea-autocomplete');
        }

        // check for embedded associations
        $propertyNameParts = explode('.', $propertyName);
        if (\count($propertyNameParts) > 1) {
            // prepare starting class for association
            $targetEntityFqcn = $entityDto->getPropertyMetadata($propertyNameParts[0])->get('targetEntity');
            array_shift($propertyNameParts);
            $metadata = $this->entityFactory->getEntityMetadata($targetEntityFqcn);

            foreach ($propertyNameParts as $association) {
                if (!$metadata->hasAssociation($association)) {
                    throw new \RuntimeException(sprintf('There is no association for the class "%s" with name "%s"', $targetEntityFqcn, $association));
                }

                // overwrite next class from association
                $targetEntityFqcn = $metadata->getAssociationTargetClass($association);

                // read next association metadata
                $metadata = $this->entityFactory->getEntityMetadata($targetEntityFqcn);
            }

            $accessor = new PropertyAccessor();
            $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER);

            $field->setFormTypeOptionIfNotSet('class', $targetEntityFqcn);

            try {
                $relatedEntityId = $accessor->getValue($entityDto->getInstance(), $propertyName.'.'.$metadata->getIdentifierFieldNames()[0]);
                $relatedEntityDto = $this->entityFactory->create($targetEntityFqcn, $relatedEntityId);

                $field->setCustomOption(AssociationField::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($targetCrudControllerFqcn, $relatedEntityDto));
                $field->setFormattedValue($this->formatAsString($relatedEntityDto->getInstance(), $relatedEntityDto));
            } catch (UnexpectedTypeException $e) {
                // this may crash if something in the tree is null, so just do nothing then
            }
        } else {
            if ($entityDto->isToOneAssociation($propertyName)) {
                $this->configureToOneAssociation($field);
            }

            if ($entityDto->isToManyAssociation($propertyName)) {
                $this->configureToManyAssociation($field);
            }
        }

        if (true === $field->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE)) {
            $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER);
            if (null === $targetCrudControllerFqcn) {
                throw new \RuntimeException(sprintf('The "%s" field cannot be autocompleted because it doesn\'t define the related CRUD controller FQCN with the "setCrudController()" method.', $field->getProperty()));
            }

            $field->setFormType(CrudAutocompleteType::class);
            $autocompleteEndpointUrl = $this->adminUrlGenerator
                ->unsetAll()
                ->set('page', 1) // The autocomplete should always start on the first page
                ->setController($field->getCustomOption(AssociationField::OPTION_CRUD_CONTROLLER))
                ->setAction('autocomplete')
                ->set(AssociationField::PARAM_AUTOCOMPLETE_CONTEXT, [
                    EA::CRUD_CONTROLLER_FQCN => $context->getRequest()->query->get(EA::CRUD_CONTROLLER_FQCN),
                    'propertyName' => $propertyName,
                    'originatingPage' => $context->getCrud()->getCurrentPage(),
                ])
                ->generateUrl();

            $field->setFormTypeOption('attr.data-ea-autocomplete-endpoint-url', $autocompleteEndpointUrl);
        } else {
            $field->setFormTypeOptionIfNotSet('query_builder', static function (EntityRepository $repository) use ($field) {
                // TODO: should this use `createIndexQueryBuilder` instead, so we get the default ordering etc.?
                // it would then be identical to the one used in autocomplete action, but it is a bit complex getting it in here
                $queryBuilder = $repository->createQueryBuilder('entity');
                if ($queryBuilderCallable = $field->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE)) {
                    $queryBuilderCallable($queryBuilder);
                }

                return $queryBuilder;
            });
        }
    }

    private function configureToOneAssociation(FieldDto $field): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toOne');

        if (false === $field->getFormTypeOption('required')) {
            $field->setFormTypeOptionIfNotSet('attr.placeholder', $this->translator->trans('label.form.empty_value', [], 'EasyAdminBundle'));
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
            $field->setTextAlign(TextAlign::RIGHT);
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

    private function generateLinkToAssociatedEntity(?string $crudController, EntityDto $entityDto): ?string
    {
        if (null === $crudController) {
            return null;
        }

        // TODO: check if user has permission to see the related entity
        return $this->adminUrlGenerator
            ->setController($crudController)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityDto->getPrimaryKeyValue())
            ->unset(EA::MENU_INDEX)
            ->unset(EA::SUBMENU_INDEX)
            ->includeReferrer()
            ->generateUrl();
    }

    private function countNumElements($collection): int
    {
        if (null === $collection) {
            return 0;
        }

        if (is_countable($collection)) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }
}
