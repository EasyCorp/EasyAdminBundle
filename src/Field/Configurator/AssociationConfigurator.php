<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use function Symfony\Component\Translation\t;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AssociationConfigurator implements FieldConfiguratorInterface
{
    /**
     * @internal the CRUD controller and pages of the embedded CRUD form that defines a field,
     *           which the autocomplete URL must use instead of the ones of the current request
     */
    public const OPTION_EMBEDDED_CRUD_FORM_AUTOCOMPLETE_CONTEXT = 'embeddedCrudFormAutocompleteContext';
    /**
     * @internal the page name passed to configureFields() when it's not the originating page (e.g. a
     *           custom page name of an embedded CRUD form)
     */
    public const AUTOCOMPLETE_CONTEXT_PAGE_NAME = 'crudControllerPageName';

    public function __construct(
        private readonly EntityFactory $entityFactory,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly RequestStack $requestStack,
        private readonly ControllerFactory $controllerFactory,
        private readonly ?FieldFactory $fieldFactory = null,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
    ) {
        if (null === $this->fieldFactory) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Not passing argument "$fieldFactory" to the "%s" constructor is deprecated.',
                self::class
            );
        }
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return AssociationField::class === $field->getFieldFqcn();
    }

    /**
     * @internal
     */
    public static function setEmbeddedCrudFormAutocompleteContext(FieldCollection $fields, string $crudControllerFqcn, string $crudPageName, string $crudControllerPageName): void
    {
        $autocompleteContext = [
            EA::CRUD_CONTROLLER_FQCN => $crudControllerFqcn,
            'originatingPage' => $crudPageName,
        ];
        if ($crudControllerPageName !== $crudPageName) {
            $autocompleteContext[self::AUTOCOMPLETE_CONTEXT_PAGE_NAME] = $crudControllerPageName;
        }

        foreach ($fields as $fieldDto) {
            $fieldDto->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_AUTOCOMPLETE_CONTEXT, $autocompleteContext);
        }
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $propertyName = $field->getProperty();

        if (!$this->isAssociation($entityDto->getClassMetadata(), $propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" field is not a Doctrine association, so it cannot be used as an association field.', $propertyName));
        }

        // the target CRUD controller can be NULL; in that case, field value doesn't link to the related entity
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER)
            ?? $context->getAdminControllers()->findCrudControllerByEntity($entityDto->getClassMetadata()->getAssociationTargetClass($propertyName));

        if (true === $field->getCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM)) {
            if (false === $entityDto->getClassMetadata()->isSingleValuedAssociation($propertyName)) {
                throw new \RuntimeException(
                    sprintf(
                        'The "%s" association field of "%s" is a to-many association but it\'s trying to use the "renderAsEmbeddedForm()" option, which is only available for to-one associations. If you want to use a CRUD form to render to-many associations, use a CollectionField instead of the AssociationField.',
                        $field->getProperty(),
                        $context->getCrud()?->getControllerFqcn(),
                    )
                );
            }

            if (null === $targetCrudControllerFqcn) {
                throw new \RuntimeException(
                    sprintf(
                        'The "%s" association field of "%s" wants to render its contents using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "%s" or pass the CRUD controller to use as the first argument of the "renderAsEmbeddedForm()" method.',
                        $field->getProperty(),
                        $context->getCrud()?->getControllerFqcn(),
                        $entityDto->getClassMetadata()->getAssociationTargetClass($propertyName)
                    )
                );
            }

            $this->configureCrudForm(
                $field,
                $entityDto,
                $propertyName,
                $entityDto->getClassMetadata()->getAssociationTargetClass($propertyName),
                $targetCrudControllerFqcn,
            );
            $this->applyEntityPermission($field, $context, $targetCrudControllerFqcn);

            return;
        }

        $field->setCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, $targetCrudControllerFqcn);

        if (AssociationField::WIDGET_AUTOCOMPLETE === $field->getCustomOption(AssociationField::OPTION_WIDGET)) {
            $field->setFormTypeOption('attr.data-ea-widget', 'ea-autocomplete');
        }

        // both autocomplete(renderAsHtml: true) and renderAsHtml(true) set the same option.
        // OPTION_ESCAPE_HTML_CONTENTS has inverted logic (true = escape, false = render as HTML)
        $field->setFormTypeOption('attr.data-ea-autocomplete-render-items-as-html', true === $field->getCustomOption(AssociationField::OPTION_ESCAPE_HTML_CONTENTS) ? 'false' : 'true');

        // check for embedded associations
        $propertyNameParts = explode('.', $propertyName);
        if (\count($propertyNameParts) > 1) {
            // prepare starting class for association
            /** @var class-string $targetEntityFqcn */
            $targetEntityFqcn = $entityDto->getClassMetadata()->getAssociationTargetClass($propertyNameParts[0]);
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
            $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER);

            $field->setFormTypeOptionIfNotSet('class', $targetEntityFqcn);

            $this->configurePreferredChoices($field);

            try {
                if (null !== $entityDto->getInstance()) {
                    $relatedEntityId = $accessor->getValue($entityDto->getInstance(), $propertyName.'.'.$metadata->getSingleIdentifierFieldName());
                    $relatedEntityDto = $this->entityFactory->create($targetEntityFqcn, $relatedEntityId);

                    $field->setCustomOption(AssociationField::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($targetCrudControllerFqcn, $relatedEntityDto));
                    $field->setFormattedValue($this->formatAsString($relatedEntityDto->getInstance(), $relatedEntityDto));
                }
            } catch (UnexpectedTypeException|RouteNotFoundException) {
                // this may throw an exception if:
                //   * something in the tree is null; do nothing in that case;
                //   * the route is not found, which happens when the associated entity is not accessible from this dashboard; do nothing in that case either.
            }
        } else {
            if ($entityDto->getClassMetadata()->isSingleValuedAssociation($propertyName)) {
                $this->configureToOneAssociation($field, $entityDto);
            }

            if ($entityDto->getClassMetadata()->isCollectionValuedAssociation($propertyName)) {
                $this->configureToManyAssociation($field, $entityDto);
            }
        }

        if (true === $field->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE)) {
            $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER);
            if (null === $targetCrudControllerFqcn) {
                throw new \RuntimeException(sprintf('The "%s" field cannot be autocompleted because it doesn\'t define the related CRUD controller FQCN with the "setCrudController()" method.', $field->getProperty()));
            }

            $field->setFormType(CrudAutocompleteType::class);

            try {
                $autocompleteEndpointUrl = $this->adminUrlGenerator
                    ->unsetAll()
                    ->set('page', 1) // The autocomplete should always start on the first page
                    ->setController($targetCrudControllerFqcn)
                    ->setAction('autocomplete')
                    ->set(AssociationField::PARAM_AUTOCOMPLETE_CONTEXT, $this->getAutocompleteContext($field, $context, $propertyName))
                    ->generateUrl();
            } catch (RouteNotFoundException $e) {
                // this may throw a "route not found" exception if the associated entity is not
                // accessible from this dashboard; do nothing in that case.
            }

            $field->setFormTypeOption('attr.data-ea-autocomplete-endpoint-url', $autocompleteEndpointUrl ?? null);

            // pass autocomplete options to render the selected item the same as the other entries
            $autocompleteCallback = $field->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE_CALLBACK);
            $autocompleteTemplate = $field->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE_TEMPLATE);

            if (null !== $autocompleteCallback) {
                $field->setFormTypeOption('autocomplete_callback', $autocompleteCallback);
            } elseif (null !== $autocompleteTemplate) {
                $field->setFormTypeOption('autocomplete_template', $autocompleteTemplate);
            }
        } else {
            $field->setFormTypeOptionIfNotSet('query_builder', static function (EntityRepository $repository) use ($field) {
                // TODO: should this use `createIndexQueryBuilder` instead, so we get the default ordering etc.?
                // it would then be identical to the one used in autocomplete action, but it is a bit complex getting it in here
                $queryBuilder = $repository->createQueryBuilder('entity');
                if (null !== $queryBuilderCallable = $field->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE)) {
                    $queryBuilder = $queryBuilderCallable($queryBuilder) ?? $queryBuilder;
                }

                return $queryBuilder;
            });
        }
    }

    /**
     * Recursive check if a string is a Doctrine association (e.g. "foo") or a nested Doctrine
     * association (e.g. "foo.bar").
     */
    private function isAssociation(ClassMetadata $entityClassMetadata, string $property): bool
    {
        $nestedProperties = explode('.', $property);

        $nextProperty = array_shift($nestedProperties);

        if (!$entityClassMetadata->hasAssociation($nextProperty)) {
            return false;
        } elseif (0 === \count($nestedProperties)) {
            return true;
        }

        return $this->isAssociation(
            $this->entityFactory->getEntityMetadata($entityClassMetadata->getAssociationTargetClass($nextProperty)),
            implode('.', $nestedProperties),
        );
    }

    private function configureToOneAssociation(FieldDto $field, EntityDto $entityDto): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toOne');

        if (false === $field->getFormTypeOption('required')) {
            $field->setFormTypeOptionIfNotSet('attr.placeholder', t('label.form.empty_value', [], 'EasyAdminBundle'));
        }

        $targetEntityFqcn = $entityDto->getClassMetadata()->getAssociationTargetClass($field->getProperty());
        $targetCrudControllerFqcn = $field->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER);

        $targetEntityDto = null === $field->getValue()
            ? $this->entityFactory->create($targetEntityFqcn)
            : $this->entityFactory->createForEntityInstance($field->getValue());
        $field->setFormTypeOptionIfNotSet('class', $targetEntityDto->getFqcn());

        $this->configurePreferredChoices($field);

        try {
            $field->setCustomOption(AssociationField::OPTION_RELATED_URL, $this->generateLinkToAssociatedEntity($targetCrudControllerFqcn, $targetEntityDto));
        } catch (RouteNotFoundException $e) {
            // this may throw a "route not found" exception if the associated entity is not
            // accessible from this dashboard; do nothing in that case.
        }

        $field->setFormattedValue($this->formatAsString($field->getValue(), $targetEntityDto));
    }

    private function configureToManyAssociation(FieldDto $field, EntityDto $entityDto): void
    {
        $field->setCustomOption(AssociationField::OPTION_DOCTRINE_ASSOCIATION_TYPE, 'toMany');

        $field->setFormTypeOptionIfNotSet('multiple', true);

        /* @var PersistentCollection $collection */
        $field->setFormTypeOptionIfNotSet('class', $entityDto->getClassMetadata()->getAssociationTargetClass($field->getProperty()));

        $this->configurePreferredChoices($field);

        if (null === $field->getTextAlign()) {
            $field->setTextAlign(TextAlign::RIGHT);
        }

        $field->setFormattedValue($this->countNumElements($field->getValue()));
    }

    private function formatAsString(mixed $entityInstance, EntityDto $entityDto): ?string
    {
        if (null === $entityInstance) {
            return null;
        }

        if ($entityInstance instanceof \Stringable) {
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

        $primaryKeyValue = $entityDto->getPrimaryKeyValue();
        // when processing fields for an entity in the index page, the primary key of the
        // associated entity is null (e.g. admin_post_index and Post <-> User)
        $crudAction = null === $primaryKeyValue ? Action::INDEX : Action::DETAIL;

        // TODO: check if user has permission to see the related entity
        return $this->adminUrlGenerator
            ->setController($crudController)
            ->setAction($crudAction)
            ->setEntityId($primaryKeyValue)
            ->unset(EA::FILTERS)
            ->unset(EA::PAGE)
            ->unset(EA::QUERY)
            ->unset(EA::SORT)
            ->generateUrl();
    }

    private function countNumElements(mixed $collection): int
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

    /**
     * @param class-string $targetEntityFqcn
     * @param class-string $targetCrudControllerFqcn
     */
    private function configureCrudForm(FieldDto $field, EntityDto $entityDto, string $propertyName, string $targetEntityFqcn, string $targetCrudControllerFqcn): void
    {
        $field->setFormType(CrudFormType::class);
        $propertyAccessor = new PropertyAccessor();

        if (null === $entityDto->getInstance()) {
            $associatedEntity = null;
        } else {
            $associatedEntity = $propertyAccessor->isReadable($entityDto->getInstance(), $propertyName)
                ? $propertyAccessor->getValue($entityDto->getInstance(), $propertyName)
                : null;
        }

        if (null === $associatedEntity) {
            $targetCrudControllerAction = Action::NEW;
            $targetCrudControllerPageName = $field->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME) ?? Crud::PAGE_NEW;
            $crudPageName = Crud::PAGE_NEW;
        } else {
            $targetCrudControllerAction = Action::EDIT;
            $targetCrudControllerPageName = $field->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME) ?? Crud::PAGE_EDIT;
            $crudPageName = Crud::PAGE_EDIT;
        }

        $field->setFormTypeOption(
            'entityDto',
            $this->createEntityDto($targetEntityFqcn, $targetCrudControllerFqcn, $targetCrudControllerAction, $targetCrudControllerPageName, $crudPageName),
        );
    }

    /**
     * The embedded form edits the associated entity (or creates it, when there's none) with the
     * CRUD form of the target controller, so it must respect its entity permission as its own
     * 'edit' and 'new' pages do.
     */
    private function applyEntityPermission(FieldDto $field, AdminContext $context, string $targetCrudControllerFqcn): void
    {
        if (null === $this->authorizationChecker
            || null === $entityPermission = $this->getEntityPermission($context, $targetCrudControllerFqcn)) {
            return;
        }

        $authorizationChecker = $this->authorizationChecker;
        $field->setCustomOption(
            CrudFormType::FIELD_OPTION_IS_ENTITY_GRANTED,
            static fn (mixed $associatedEntity): bool => $authorizationChecker->isGranted($entityPermission, \is_object($associatedEntity) ? $associatedEntity : null),
        );
    }

    /**
     * Returns the entity permission of the given CRUD controller, built in the same way as
     * the CRUD context of its own pages (dashboard defaults overridden by the controller).
     */
    private function getEntityPermission(AdminContext $context, string $crudControllerFqcn): string|Expression|null
    {
        $request = $this->requestStack->getMainRequest();
        $dashboardController = $this->controllerFactory->getDashboardControllerInstance($context->getDashboardControllerFqcn(), $request);
        $crudController = $this->controllerFactory->getCrudControllerInstance($crudControllerFqcn, Action::EDIT, $request);

        return $crudController->configureCrud($dashboardController?->configureCrud() ?? Crud::new())->getAsDto()->getEntityPermission();
    }

    /**
     * @param class-string $entityFqcn
     * @param class-string $crudControllerFqcn
     */
    private function createEntityDto(string $entityFqcn, string $crudControllerFqcn, string $crudControllerAction, string $crudControllerPageName, string $crudPageName): EntityDto
    {
        $entityDto = $this->entityFactory->create($entityFqcn);

        $crudController = $this->controllerFactory->getCrudControllerInstance(
            $crudControllerFqcn,
            $crudControllerAction,
            $this->requestStack->getMainRequest()
        );

        $fields = new FieldCollection($crudController->configureFields($crudControllerPageName));
        self::setEmbeddedCrudFormAutocompleteContext($fields, $crudControllerFqcn, $crudPageName, $crudControllerPageName);

        if (null === $this->fieldFactory) {
            $this->entityFactory->processFields($entityDto, $fields, $crudPageName);
        } else {
            $this->fieldFactory->processFields($entityDto, $fields, $crudPageName);
        }

        return $entityDto;
    }

    /**
     * The autocomplete action looks for the field in the CRUD controller, page and property
     * of this context, so they must be the ones used to define the field.
     *
     * @return array<string, mixed>
     */
    private function getAutocompleteContext(FieldDto $field, AdminContext $context, string $propertyName): array
    {
        $embeddedCrudFormContext = $field->getCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_AUTOCOMPLETE_CONTEXT);
        if (\is_array($embeddedCrudFormContext)) {
            return ['propertyName' => $propertyName] + $embeddedCrudFormContext;
        }

        $request = $context->getRequest();
        $currentPage = $context->getCrud()->getCurrentPage();
        $autocompleteContext = [
            // when using pretty URLs, the data is in the request attributes instead of the autocomplete context
            EA::CRUD_CONTROLLER_FQCN => $request->attributes->get(EA::CRUD_CONTROLLER_FQCN) ?? $request->query->get(EA::CRUD_CONTROLLER_FQCN),
            'propertyName' => $propertyName,
            'originatingPage' => $currentPage,
        ];

        // needed when the permission of the page depends on the entity (e.g. a voter for the 'edit' page);
        // the 'new' page has no entity, even if the query string contains an ID
        $entityId = $request->attributes->get(EA::ENTITY_ID) ?? $request->query->get(EA::ENTITY_ID);
        if (Crud::PAGE_NEW !== $currentPage && \is_scalar($entityId)) {
            $autocompleteContext[EA::ENTITY_ID] = (string) $entityId;
        }

        return $autocompleteContext;
    }

    private function configurePreferredChoices(FieldDto $field): void
    {
        $preferredChoices = $field->getCustomOption(AssociationField::OPTION_PREFERRED_CHOICES);
        if (null !== $preferredChoices) {
            $field->setFormTypeOptionIfNotSet('preferred_choices', $preferredChoices);
        }
    }
}
