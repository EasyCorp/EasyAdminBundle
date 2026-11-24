<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityUpdaterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\AssociationConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FileUploadState;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 *
 * @implements  CrudControllerInterface<TEntity>
 */
abstract class AbstractCrudController extends AbstractController implements CrudControllerInterface
{
    abstract public static function getEntityFqcn(): string;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->container->get(FieldProvider::class)->getDefaultFields($pageName);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine' => '?'.ManagerRegistry::class,
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            ActionFactory::class => '?'.ActionFactory::class,
            AdminContextFactory::class => '?'.AdminContextFactory::class,
            AdminContextProviderInterface::class => '?'.AdminContextProviderInterface::class,
            AdminRouteGenerator::class => '?'.AdminRouteGenerator::class,
            AdminUrlGeneratorInterface::class => '?'.AdminUrlGeneratorInterface::class,
            ControllerFactory::class => '?'.ControllerFactory::class,
            EntityFactory::class => '?'.EntityFactory::class,
            EntityRepositoryInterface::class => '?'.EntityRepositoryInterface::class,
            EntityUpdaterInterface::class => '?'.EntityUpdaterInterface::class,
            FieldProvider::class => '?'.FieldProvider::class,
            FilterFactory::class => '?'.FilterFactory::class,
            FormFactory::class => '?'.FormFactory::class,
            PaginatorFactory::class => '?'.PaginatorFactory::class,
            FieldFactory::class => '?'.FieldFactory::class,
            // the following keys are kept for BC reasons (they were replaced by the corresponding interfaces)
            AdminContextProvider::class => '?'.AdminContextProviderInterface::class,
            AdminUrlGenerator::class => '?'.AdminUrlGeneratorInterface::class,
            EntityRepository::class => '?'.EntityRepositoryInterface::class,
            EntityUpdater::class => '?'.EntityUpdaterInterface::class,
        ]);
    }

    public function index(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::INDEX, 'entity' => null, 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        $fields = new FieldCollection($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $paginator = $this->container->get(PaginatorFactory::class)->create($queryBuilder);

        // this can happen after deleting some items and trying to return
        // to a 'index' page that no longer exists. Redirect to the last page instead
        if ($paginator->isOutOfRange()) {
            return $this->redirect($this->container->get(AdminUrlGeneratorInterface::class)
                ->set(EA::PAGE, $paginator->getLastPage())
                ->generateUrl());
        }

        $entities = $this->container->get(EntityFactory::class)->createCollection($context->getEntity(), $paginator->getResults());
        $this->container->get(FieldFactory::class)->processFieldsForAll($entities, $fields, Crud::PAGE_INDEX);
        $processedFields = $entities->first()?->getFields() ?? new FieldCollection([]);
        $context->getCrud()->setFieldAssets($this->getFieldAssets($processedFields));
        $actions = $this->container->get(ActionFactory::class)->processGlobalActionsAndEntityActionsForAll($entities, $context->getCrud()->getActionsConfig());

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_INDEX,
            'templateName' => 'crud/index',
            'entities' => $entities,
            'paginator' => $paginator,
            'global_actions' => $actions->getGlobalActions(),
            'batch_actions' => $actions->getBatchActions(),
            'filters' => $filters,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function detail(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::DETAIL, 'entity' => $context->getEntity(), 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->container->get(FieldFactory::class)->processFields($context->getEntity(), new FieldCollection($this->configureFields(Crud::PAGE_DETAIL)), Crud::PAGE_DETAIL);
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(ActionFactory::class)->processEntityActions($context->getEntity(), $context->getCrud()->getActionsConfig());

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_DETAIL,
            'templateName' => 'crud/detail',
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function edit(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::EDIT, 'entity' => $context->getEntity(), 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->container->get(FieldFactory::class)->processFields($context->getEntity(), new FieldCollection($this->configureFields(Crud::PAGE_EDIT)), Crud::PAGE_EDIT);
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(ActionFactory::class)->processEntityActions($context->getEntity(), $context->getCrud()->getActionsConfig());
        /** @var TEntity $entityInstance */
        $entityInstance = $context->getEntity()->getInstance();

        if ($context->getRequest()->isXmlHttpRequest()) {
            if ('PATCH' !== $context->getRequest()->getMethod()) {
                throw new MethodNotAllowedHttpException(['PATCH']);
            }

            if (!$this->isCsrfTokenValid(BooleanField::CSRF_TOKEN_NAME, $context->getRequest()->query->get('csrfToken'))) {
                if (class_exists(InvalidCsrfTokenException::class)) {
                    throw new InvalidCsrfTokenException();
                }

                return new Response('Invalid CSRF token.', 400);
            }

            $fieldName = $context->getRequest()->query->get('fieldName');
            $newValue = 'true' === mb_strtolower($context->getRequest()->query->get('newValue'));

            try {
                $event = $this->ajaxEdit($context->getEntity(), $fieldName, $newValue);
            } catch (\Exception $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            return new Response($newValue ? '1' : '0');
        }

        $editForm = $this->createEditForm($context->getEntity(), $context->getCrud()->getEditFormOptions(), $context);
        $editForm->handleRequest($context->getRequest());
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->processUploadedFiles($editForm);

            $event = new BeforeEntityUpdatedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->updateEntity($this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);

            $event = new AfterEntityUpdatedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            return $this->getRedirectResponseAfterSave($context, Action::EDIT);
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_EDIT,
            'templateName' => 'crud/edit',
            'edit_form' => $editForm,
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function new(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::NEW, 'entity' => null, 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        /** @var class-string<TEntity> $entityFqcn */
        $entityFqcn = $context->getEntity()->getFqcn();
        $context->getEntity()->setInstance($this->createEntity($entityFqcn));
        $this->container->get(FieldFactory::class)->processFields($context->getEntity(), new FieldCollection($this->configureFields(Crud::PAGE_NEW)), Crud::PAGE_NEW);
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(ActionFactory::class)->processEntityActions($context->getEntity(), $context->getCrud()->getActionsConfig());

        $newForm = $this->createNewForm($context->getEntity(), $context->getCrud()->getNewFormOptions(), $context);
        $newForm->handleRequest($context->getRequest());

        /** @var TEntity $entityInstance */
        $entityInstance = $newForm->getData();
        $context->getEntity()->setInstance($entityInstance);

        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->processUploadedFiles($newForm);

            $event = new BeforeEntityPersistedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->persistEntity($this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);

            $event = new AfterEntityPersistedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $context->getEntity()->setInstance($entityInstance);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            return $this->getRedirectResponseAfterSave($context, Action::NEW);
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_NEW,
            'templateName' => 'crud/new',
            'entity' => $context->getEntity(),
            'new_form' => $newForm,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function delete(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::DELETE, 'entity' => $context->getEntity(), 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $csrfToken = $context->getRequest()->request->has('token')
            ? (string) $context->getRequest()->request->get('token')
            : null;
        if (!$this->isCsrfTokenValid('ea-delete', $csrfToken)) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        /** @var TEntity $entityInstance */
        $entityInstance = $context->getEntity()->getInstance();

        $event = new BeforeEntityDeletedEvent($entityInstance);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }
        $entityInstance = $event->getEntityInstance();

        try {
            $this->deleteEntity($this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new EntityRemoveException(['entity_name' => $context->getEntity()->getName(), 'message' => $e->getMessage()], $e);
        }

        $event = new AfterEntityDeletedEvent($entityInstance);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->redirect($this->container->get(AdminUrlGeneratorInterface::class)->setController($context->getCrud()->getControllerFqcn())->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    /**
     * @param BatchActionDto<TEntity> $batchActionDto
     */
    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::BATCH_DELETE, 'entity' => null, 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$this->isCsrfTokenValid('ea-batch-action-'.Action::BATCH_DELETE.'-'.$batchActionDto->getEntityFqcn(), $batchActionDto->getCsrfToken())) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        // the entity FQCN in the batch action DTO comes from the POST body and is
        // attacker-controlled. The FQCN in the admin context comes from the URL-routed
        // CRUD controller. If they disagree, an admin with batch-delete rights on one
        // CRUD could delete rows of any other Doctrine entity. Reject the mismatch.
        if ($batchActionDto->getEntityFqcn() !== $context->getEntity()->getFqcn()) {
            throw new BadRequestHttpException();
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());

        // the submitted IDs are user input, so every entity is authorized before deleting any of
        // them; otherwise a batch mixing allowed and forbidden entities would be deleted partially
        $authorizedEntityIds = [];
        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entityInstance = $repository->find($entityId);
            if (null === $entityInstance) {
                continue;
            }

            $entityDto = $context->getEntity()->newWithInstance($entityInstance);
            if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::DELETE, 'entity' => $entityDto, 'entityFqcn' => $context->getEntity()->getFqcn()])) {
                throw new ForbiddenActionException($context);
            }

            // newWithInstance() doesn't evaluate the entity permission, so it must be checked here
            if (!$this->isGranted(Permission::EA_ACCESS_ENTITY, $entityDto)) {
                throw new InsufficientEntityPermissionException($context);
            }

            $authorizedEntityIds[] = $entityId;
        }

        // the authorized entities stay managed until they are deleted, which makes each flush()
        // of deleteEntity() slower as the batch grows; detaching them is not an option because
        // other managed objects (e.g. collections loaded by listeners or voters) may reference them
        foreach ($authorizedEntityIds as $entityId) {
            $entityInstance = $repository->find($entityId);
            if (null === $entityInstance) {
                continue;
            }

            $entityDto = $context->getEntity()->newWithInstance($entityInstance);
            $event = new BeforeEntityDeletedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }
            $entityInstance = $event->getEntityInstance();

            try {
                $this->deleteEntity($entityManager, $entityInstance);
            } catch (ForeignKeyConstraintViolationException $e) {
                throw new EntityRemoveException(['entity_name' => (string) $entityDto, 'message' => $e->getMessage()], $e);
            }

            $event = new AfterEntityDeletedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'entity' => $context->getEntity(),
            'batchActionDto' => $batchActionDto,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        // resetting the page number is needed because after deleting some entities, the pagination will change
        return $this->redirect($this->container->get(AdminUrlGeneratorInterface::class)->setAction(Action::INDEX)->set(EA::PAGE, 1)->generateUrl());
    }

    public function autocomplete(AdminContext $context): JsonResponse
    {
        // not a typo; we intentionally reuse the INDEX action for permission checks in autocomplete
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::INDEX, 'entity' => null, 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), new FieldCollection([]), new FilterCollection());

        $autocompleteContext = $context->getRequest()->query->all(AssociationField::PARAM_AUTOCOMPLETE_CONTEXT);

        if (!isset($autocompleteContext['originatingPage'], $autocompleteContext['propertyName'])) {
            throw new \RuntimeException('Invalid autocomplete context: missing required parameters "originatingPage" or "propertyName".');
        }

        $crudControllerFqcn = $autocompleteContext[EA::CRUD_CONTROLLER_FQCN] ?? $context->getRequest()->attributes->get(EA::CRUD_CONTROLLER_FQCN) ?? $context->getRequest()->query->get(EA::CRUD_CONTROLLER_FQCN);
        $originatingPage = $autocompleteContext['originatingPage'];
        $propertyName = $autocompleteContext['propertyName'];
        $crudControllerPageName = $autocompleteContext[AssociationConfigurator::AUTOCOMPLETE_CONTEXT_PAGE_NAME] ?? $originatingPage;
        $originatingEntityId = $autocompleteContext[EA::ENTITY_ID] ?? null;
        if (!\is_string($crudControllerFqcn) || !\is_string($originatingPage) || !\is_string($propertyName) || !\is_string($crudControllerPageName) || (null !== $originatingEntityId && !\is_string($originatingEntityId))) {
            throw new BadRequestHttpException('Invalid autocomplete context: its values must be strings.');
        }

        // needed to fix the double encoding of URLs that might happen (https://github.com/EasyCorp/EasyAdminBundle/pull/6902)
        $crudControllerFqcn = str_replace('%5C', '\\', $crudControllerFqcn);

        // the autocomplete context comes from the query string, so the options of the field that it
        // names are only applied if the user can access the page that displays that field
        if (!$this->isAutocompleteContextGranted($context, $crudControllerFqcn, $originatingPage, $originatingEntityId)) {
            throw new ForbiddenActionException($context);
        }

        /** @var CrudControllerInterface $controller */
        $controller = $this->container->get(ControllerFactory::class)->getCrudControllerInstance($crudControllerFqcn, Action::INDEX, $context->getRequest());
        $fields = new FieldCollection($controller->configureFields($crudControllerPageName));

        // find the first field with matching property that is displayed on the originating page;
        // this ensures we get the correct field when a controller defines more than one field for the
        // same property, each of them displayed only on certain pages (via hideOnIndex(), onlyOnForms(), etc.)
        /** @var FieldDto|null $field */
        $field = null;
        foreach ($fields as $fieldDto) {
            if ($propertyName === $fieldDto->getProperty() && $fieldDto->isDisplayedOn($originatingPage) && $this->isGranted(Permission::EA_VIEW_FIELD, $fieldDto)) {
                $field = $fieldDto;
                break;
            }
        }

        /** @var \Closure|null $queryBuilderCallable */
        $queryBuilderCallable = $field?->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE);

        if (null !== $queryBuilderCallable) {
            $queryBuilder = $queryBuilderCallable($queryBuilder) ?? $queryBuilder;

            // the callable can return a new query builder (e.g. created by a repository), but the
            // permission checked above only allows listing the entity of this CRUD controller
            foreach ($queryBuilder->getRootEntities() as $rootEntityFqcn) {
                if (!is_a($rootEntityFqcn, $context->getEntity()->getFqcn(), true)) {
                    throw new ForbiddenActionException($context);
                }
            }
        }

        $callback = $field?->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE_CALLBACK)
            ?? $context->getCrud()?->getAutocompleteCallback();

        $template = $field?->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE_TEMPLATE)
            ?? $context->getCrud()?->getAutocompleteTemplate();

        // at field-level, the option is OPTION_ESCAPE_HTML_CONTENTS, which is "render as HTML" inverted
        $fieldEscapeHtml = $field?->getCustomOption(AssociationField::OPTION_ESCAPE_HTML_CONTENTS);
        $renderAsHtml = (null !== $fieldEscapeHtml && false === $fieldEscapeHtml)
            || (null === $fieldEscapeHtml && $context->getCrud()?->getAutocompleteRenderAsHtml());

        $paginator = $this->container->get(PaginatorFactory::class)->create($queryBuilder);

        return JsonResponse::fromJsonString($paginator->getResultsAsJson($callback, $template, $renderAsHtml));
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return $this->container->get(EntityRepositoryInterface::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    public function renderFilters(AdminContext $context): KeyValueStore
    {
        // not a typo; the filter form is a sub-component of the INDEX page,
        // so we reuse the INDEX action for permission checks here
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::INDEX, 'entity' => null, 'entityFqcn' => $context->getEntity()->getFqcn()])) {
            throw new ForbiddenActionException($context);
        }

        $fields = new FieldCollection($this->configureFields(Crud::PAGE_INDEX));
        $this->container->get(FieldFactory::class)->processFields($context->getEntity(), $fields, Crud::PAGE_INDEX);
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $context->getEntity()->getFields(), $context->getEntity());

        /** @var FormInterface&FiltersFormType $filtersForm */
        $filtersForm = $this->container->get(FormFactory::class)->createFiltersForm($filters, $context->getRequest());
        $formActionParts = parse_url($filtersForm->getConfig()->getAction());
        $queryString = $formActionParts[EA::QUERY] ?? '';
        parse_str($queryString, $queryStringAsArray);
        unset($queryStringAsArray[EA::FILTERS], $queryStringAsArray[EA::PAGE]);

        $responseParameters = KeyValueStore::new([
            'templateName' => 'crud/filters',
            'filters_form' => $filtersForm,
            'form_action_query_string_as_array' => $queryStringAsArray,
        ]);

        return $this->configureResponseParameters($responseParameters);
    }

    public function createEntity(string $entityFqcn)
    {
        return new $entityFqcn();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        return $this->createEditFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->container->get(FormFactory::class)->createEditFormBuilder($entityDto, $formOptions, $context);
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        return $this->createNewFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->container->get(FormFactory::class)->createNewFormBuilder($entityDto, $formOptions, $context);
    }

    /**
     * Used to add/modify/remove parameters before passing them to the Twig template.
     */
    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        return $responseParameters;
    }

    protected function getContext(): ?AdminContext
    {
        return $this->container->get(AdminContextProviderInterface::class)->getContext();
    }

    /**
     * @param EntityDto<TEntity> $entityDto
     */
    protected function ajaxEdit(EntityDto $entityDto, ?string $propertyName, bool $newValue): AfterCrudActionEvent
    {
        $field = $entityDto->getFields()->getByProperty($propertyName);
        if (null === $field || true === $field->getFormTypeOption('disabled')) {
            throw new AccessDeniedException(sprintf('The field "%s" does not exist or it\'s configured as disabled, so it can\'t be modified.', $propertyName));
        }

        $this->container->get(EntityUpdaterInterface::class)->updateProperty($entityDto, $propertyName, $newValue);

        /** @var TEntity $entityInstance */
        $entityInstance = $entityDto->getInstance();
        $event = new BeforeEntityUpdatedEvent($entityInstance);
        $this->container->get('event_dispatcher')->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        $this->updateEntity($this->container->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

        $this->container->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

        $entityDto->setInstance($entityInstance);

        $parameters = KeyValueStore::new([
            'action' => Action::EDIT,
            'entity' => $entityDto,
        ]);

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->container->get('event_dispatcher')->dispatch($event);

        return $event;
    }

    protected function processUploadedFiles(FormInterface $form): void
    {
        /** @var FormInterface $child */
        foreach ($form as $child) {
            $config = $child->getConfig();

            if (!$config->getType()->getInnerType() instanceof FileUploadType) {
                if ($config->getCompound()) {
                    $this->processUploadedFiles($child);
                }

                continue;
            }

            /** @var FileUploadState $state */
            $state = $config->getAttribute('state');

            if (!$state->isModified()) {
                continue;
            }

            $uploadDelete = $config->getOption('upload_delete');

            if ($state->hasCurrentFiles() && ($state->isDelete() || (!$state->isAddAllowed() && $state->hasUploadedFiles()))) {
                foreach ($state->getCurrentFiles() as $file) {
                    $uploadDelete($file);
                }
                $state->setCurrentFiles([]);
            }

            $filePaths = (array) $child->getData();
            $uploadDir = $config->getOption('upload_dir');
            $uploadNew = $config->getOption('upload_new');

            foreach ($state->getUploadedFiles() as $index => $file) {
                $fileName = u($filePaths[$index])->replace($uploadDir, '')->toString();
                $uploadNew($file, $uploadDir, $fileName);
            }
        }
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'] ?? null;

        $url = match ($submitButtonName) {
            Action::SAVE_AND_CONTINUE => $this->container->get(AdminUrlGeneratorInterface::class)
                ->setAction(Action::EDIT)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl(),
            Action::SAVE_AND_RETURN => $this->container->get(AdminUrlGeneratorInterface::class)->setAction(Action::INDEX)->generateUrl(),
            Action::SAVE_AND_ADD_ANOTHER => $this->container->get(AdminUrlGeneratorInterface::class)->setAction(Action::NEW)->generateUrl(),
            default => $this->generateUrl($context->getDashboardRouteName()),
        };

        return $this->redirect($url);
    }

    protected function getFieldAssets(FieldCollection $fieldDtos): AssetsDto
    {
        $fieldAssetsDto = new AssetsDto();
        $currentPageName = $this->getContext()?->getCrud()?->getCurrentPage();
        foreach ($fieldDtos as $fieldDto) {
            $fieldAssetsDto = $fieldAssetsDto->mergeWith($fieldDto->getAssets()->loadedOn($currentPageName));
        }

        return $fieldAssetsDto;
    }

    private function isAutocompleteContextGranted(AdminContext $context, string $crudControllerFqcn, string $pageName, ?string $entityId): bool
    {
        $isCurrentCrudController = $crudControllerFqcn === $context->getCrud()?->getControllerFqcn();
        $entityFqcn = $isCurrentCrudController
            ? $context->getEntity()->getFqcn()
            : $context->getAdminControllers()->findEntityByCrudController($crudControllerFqcn);
        if (null === $entityFqcn) {
            return false;
        }

        // the fields of other CRUD controllers are only displayed in their CRUD pages (the permissions use the exact page name)
        $crudPageNames = [Crud::PAGE_INDEX, Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW];
        if (!$isCurrentCrudController && !\in_array($pageName, $crudPageNames, true)) {
            return false;
        }

        if (!$isCurrentCrudController && !$this->container->get(AdminRouteGenerator::class)->isCrudActionAllowedInDashboard($context->getDashboardControllerFqcn(), $crudControllerFqcn, $pageName)) {
            return false;
        }

        // a fresh Request on purpose: the EA attributes of the current one (entity id, filters, sort)
        // would otherwise leak into the context of the CRUD controller that defines the field
        $request = new Request();
        $request->setLocale($context->getRequest()->getLocale());
        $controllerFactory = $this->container->get(ControllerFactory::class);
        $dashboardController = $controllerFactory->getDashboardControllerInstance($context->getDashboardControllerFqcn(), $request);
        $crudController = $controllerFactory->getCrudControllerInstance($crudControllerFqcn, Action::INDEX, $request);
        if (null === $dashboardController || null === $crudController) {
            return false;
        }

        $crudDto = $this->container->get(AdminContextFactory::class)->create($request, $dashboardController, $crudController, $pageName)->getCrud();
        if (null === $crudDto) {
            return false;
        }

        // the permission of some pages depends on the entity (e.g. a voter that decides who can edit each entity);
        // like the pages themselves, the entity permission is checked with no entity in the 'new' page
        $entityDto = null;
        if (\in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            try {
                $entityDto = $this->container->get(EntityFactory::class)->create($entityFqcn, Crud::PAGE_NEW === $pageName ? null : $entityId, $crudDto->getEntityPermission());
            } catch (EntityNotFoundException) {
                return false;
            }

            if (!$entityDto->isAccessible()) {
                return false;
            }
        }

        return $this->isGranted(Permission::EA_EXECUTE_ACTION, ['crud' => $crudDto, 'action' => $pageName, 'entity' => $entityDto, 'entityFqcn' => $entityFqcn]);
    }
}
