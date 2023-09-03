<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Closure;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionsInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\AssetsInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\CrudInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\FiltersInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStoreInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FileUploadState;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\PermissionInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractCrudController extends AbstractController implements CrudControllerInterface
{
    abstract public static function getEntityFqcn(): string;

    public function configureCrud(CrudInterface $crud): CrudInterface
    {
        return $crud;
    }

    public function configureAssets(AssetsInterface $assets): AssetsInterface
    {
        return $assets;
    }

    public function configureActions(ActionsInterface $actions): ActionsInterface
    {
        return $actions;
    }

    public function configureFilters(FiltersInterface $filters): FiltersInterface
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
            AdminContextProvider::class => '?'.AdminContextProvider::class,
            AdminUrlGenerator::class => '?'.AdminUrlGenerator::class,
            ControllerFactory::class => '?'.ControllerFactory::class,
            EntityFactory::class => '?'.EntityFactory::class,
            EntityRepository::class => '?'.EntityRepository::class,
            EntityUpdater::class => '?'.EntityUpdater::class,
            FieldProvider::class => '?'.FieldProvider::class,
            FilterFactory::class => '?'.FilterFactory::class,
            FormFactory::class => '?'.FormFactory::class,
            PaginatorFactory::class => '?'.PaginatorFactory::class,
        ]);
    }

    public function index(AdminContextInterface $context): Response|KeyValueStoreInterface
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(PermissionInterface::EA_EXECUTE_ACTION, [
            'action' => ActionInterface::INDEX,
            'entity' => null,
        ])) {
            throw new ForbiddenActionException($context);
        }

        $fields = FieldCollection::new($this->configureFields(CrudInterface::PAGE_INDEX));
        $context->getCrud()->setFieldAssets($this->getFieldAssets($fields));
        $filters = $this->container->get(FilterFactory::class)->create(
            $context->getCrud()->getFiltersConfig(),
            $fields,
            $context->getEntity()
        );
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $paginator = $this->container->get(PaginatorFactory::class)->create($queryBuilder);

        // this can happen after deleting some items and trying to return
        // to a 'index' page that no longer exists. Redirect to the last page instead
        if ($paginator->isOutOfRange()) {
            return $this->redirect(
                $this->container->get(AdminUrlGenerator::class)
                    ->set(EA::PAGE, $paginator->getLastPage())
                    ->generateUrl()
            );
        }

        $entities = $this->container->get(EntityFactory::class)->createCollection(
            $context->getEntity(),
            $paginator->getResults()
        );
        $this->container->get(EntityFactory::class)->processFieldsForAll($entities, $fields);
        $actions = $this->container->get(EntityFactory::class)->processActionsForAll(
            $entities,
            $context->getCrud()->getActionsConfig()
        );

        $responseParameters = $this->configureResponseParameters(
            KeyValueStore::new([
                'pageName' => CrudInterface::PAGE_INDEX,
                'templateName' => 'crud/index',
                'entities' => $entities,
                'paginator' => $paginator,
                'global_actions' => $actions->getGlobalActions(),
                'batch_actions' => $actions->getBatchActions(),
                'filters' => $filters,
            ])
        );

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function detail(AdminContextInterface $context): Response|KeyValueStoreInterface
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(
            PermissionInterface::EA_EXECUTE_ACTION,
            ['action' => ActionInterface::DETAIL, 'entity' => $context->getEntity()]
        )) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->container->get(EntityFactory::class)->processFields(
            $context->getEntity(),
            FieldCollection::new(
                $this->configureFields(
                    CrudInterface::PAGE_DETAIL
                )
            )
        );
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(EntityFactory::class)->processActions(
            $context->getEntity(),
            $context->getCrud()->getActionsConfig()
        );

        $responseParameters = $this->configureResponseParameters(
            KeyValueStore::new([
                'pageName' => CrudInterface::PAGE_DETAIL,
                'templateName' => 'crud/detail',
                'entity' => $context->getEntity(),
            ])
        );

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function edit(AdminContextInterface $context): Response|KeyValueStoreInterface
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(
            PermissionInterface::EA_EXECUTE_ACTION,
            ['action' => ActionInterface::EDIT, 'entity' => $context->getEntity()]
        )) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->container->get(EntityFactory::class)->processFields(
            $context->getEntity(),
            FieldCollection::new(
                $this->configureFields(
                    CrudInterface::PAGE_EDIT
                )
            )
        );
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(EntityFactory::class)->processActions(
            $context->getEntity(),
            $context->getCrud()->getActionsConfig()
        );
        $entityInstance = $context->getEntity()->getInstance();

        if ($context->getRequest()->isXmlHttpRequest()) {
            if ('PATCH' !== $context->getRequest()->getMethod()) {
                throw new MethodNotAllowedHttpException(['PATCH']);
            }

            if (!$this->isCsrfTokenValid(
                BooleanField::CSRF_TOKEN_NAME,
                $context->getRequest()->query->get('csrfToken')
            )) {
                if (class_exists(InvalidCsrfTokenException::class)) {
                    throw new InvalidCsrfTokenException();
                } else {
                    return new Response('Invalid CSRF token.', 400);
                }
            }

            $fieldName = $context->getRequest()->query->get('fieldName');
            $newValue = 'true' === mb_strtolower($context->getRequest()->query->get('newValue'));

            try {
                $event = $this->ajaxEdit($context->getEntity(), $fieldName, $newValue);
            } catch (Exception) {
                throw new BadRequestHttpException();
            }

            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            return new Response($newValue ? '1' : '0');
        }

        $editForm = $this->createEditForm(
            $context->getEntity(),
            $context->getCrud()->getEditFormOptions(),
            $context
        );
        $editForm->handleRequest($context->getRequest());
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->processUploadedFiles($editForm);

            $event = new BeforeEntityUpdatedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->updateEntity(
                $this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()),
                $entityInstance
            );

            $this->container->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

            return $this->getRedirectResponseAfterSave(
                $context,
                ActionInterface::EDIT
            );
        }

        $responseParameters = $this->configureResponseParameters(
            KeyValueStore::new([
                'pageName' => CrudInterface::PAGE_EDIT,
                'templateName' => 'crud/edit',
                'edit_form' => $editForm,
                'entity' => $context->getEntity(),
            ])
        );

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function new(AdminContextInterface $context): Response|KeyValueStoreInterface
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(
            PermissionInterface::EA_EXECUTE_ACTION,
            ['action' => ActionInterface::NEW, 'entity' => null]
        )) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $context->getEntity()->setInstance($this->createEntity($context->getEntity()->getFqcn()));
        $this->container->get(EntityFactory::class)->processFields(
            $context->getEntity(),
            FieldCollection::new(
                $this->configureFields(
                    CrudInterface::PAGE_NEW
                )
            )
        );
        $context->getCrud()->setFieldAssets($this->getFieldAssets($context->getEntity()->getFields()));
        $this->container->get(EntityFactory::class)->processActions(
            $context->getEntity(),
            $context->getCrud()->getActionsConfig()
        );

        $newForm = $this->createNewForm($context->getEntity(), $context->getCrud()->getNewFormOptions(), $context);
        $newForm->handleRequest($context->getRequest());

        $entityInstance = $newForm->getData();
        $context->getEntity()->setInstance($entityInstance);

        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->processUploadedFiles($newForm);

            $event = new BeforeEntityPersistedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->persistEntity(
                $this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()),
                $entityInstance
            );

            $this->container->get('event_dispatcher')->dispatch(new AfterEntityPersistedEvent($entityInstance));
            $context->getEntity()->setInstance($entityInstance);

            return $this->getRedirectResponseAfterSave($context, ActionInterface::NEW);
        }

        $responseParameters = $this->configureResponseParameters(
            KeyValueStore::new([
                'pageName' => CrudInterface::PAGE_NEW,
                'templateName' => 'crud/new',
                'entity' => $context->getEntity(),
                'new_form' => $newForm,
            ])
        );

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function delete(AdminContextInterface $context): Response|KeyValueStoreInterface
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(
            PermissionInterface::EA_EXECUTE_ACTION,
            ['action' => ActionInterface::DELETE, 'entity' => $context->getEntity()]
        )) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $csrfToken = $context->getRequest()->request->get('token');
        if ($this->container->has('security.csrf.token_manager') && !$this->isCsrfTokenValid('ea-delete', $csrfToken)) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $entityInstance = $context->getEntity()->getInstance();

        $event = new BeforeEntityDeletedEvent($entityInstance);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }
        $entityInstance = $event->getEntityInstance();

        try {
            $this->deleteEntity(
                $this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()),
                $entityInstance
            );
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new EntityRemoveException(
                ['entity_name' => $context->getEntity()->getName(), 'message' => $e->getMessage()]
            );
        }

        $this->container->get('event_dispatcher')->dispatch(new AfterEntityDeletedEvent($entityInstance));

        $responseParameters = $this->configureResponseParameters(
            KeyValueStore::new([
                'entity' => $context->getEntity(),
            ])
        );

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (null !== $referrer = $context->getReferrer()) {
            return $this->redirect($referrer);
        }

        return $this->redirect(
            $this->container->get(AdminUrlGenerator::class)->setAction(ActionInterface::INDEX)->unset(
                EA::ENTITY_ID
            )->generateUrl()
        );
    }

    public function batchDelete(AdminContextInterface $context, BatchActionDtoInterface $batchActionDto): Response
    {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isCsrfTokenValid(
            'ea-batch-action-'.ActionInterface::BATCH_DELETE,
            $batchActionDto->getCsrfToken()
        )) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());
        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entityInstance = $repository->find($entityId);
            if (!$entityInstance) {
                continue;
            }

            $entityDto = $context->getEntity()->newWithInstance($entityInstance);
            if (!$this->isGranted(
                PermissionInterface::EA_EXECUTE_ACTION,
                ['action' => ActionInterface::DELETE, 'entity' => $entityDto]
            )) {
                throw new ForbiddenActionException($context);
            }

            if (!$entityDto->isAccessible()) {
                throw new InsufficientEntityPermissionException($context);
            }

            $event = new BeforeEntityDeletedEvent($entityInstance);
            $this->container->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            try {
                $this->deleteEntity($entityManager, $entityInstance);
            } catch (ForeignKeyConstraintViolationException $e) {
                throw new EntityRemoveException(['entity_name' => $entityDto->toString(), 'message' => $e->getMessage()]
                );
            }

            $this->container->get('event_dispatcher')->dispatch(new AfterEntityDeletedEvent($entityInstance));
        }

        $responseParameters = $this->configureResponseParameters(
            KeyValueStore::new([
                'entity' => $context->getEntity(),
                'batchActionDto' => $batchActionDto,
            ])
        );

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function autocomplete(AdminContextInterface $context): JsonResponse
    {
        $queryBuilder = $this->createIndexQueryBuilder(
            $context->getSearch(),
            $context->getEntity(),
            FieldCollection::new([]),
            FilterCollection::new()
        );

        $autocompleteContext = $context->getRequest()->get(AssociationField::PARAM_AUTOCOMPLETE_CONTEXT);

        /** @var CrudControllerInterface $controller */
        $controller = $this->container->get(ControllerFactory::class)->getCrudControllerInstance(
            $autocompleteContext[EA::CRUD_CONTROLLER_FQCN],
            ActionInterface::INDEX,
            $context->getRequest()
        );
        /** @var FieldDtoInterface|null $field */
        $field = FieldCollection::new(
            $controller->configureFields($autocompleteContext['originatingPage'])
        )->getByProperty($autocompleteContext['propertyName']);
        /** @var Closure|null $queryBuilderCallable */
        $queryBuilderCallable = $field?->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE);

        if (null !== $queryBuilderCallable) {
            $queryBuilderCallable($queryBuilder);
        }

        $paginator = $this->container->get(PaginatorFactory::class)->create($queryBuilder);

        return JsonResponse::fromJsonString($paginator->getResultsAsJson());
    }

    public function createIndexQueryBuilder(
        SearchDtoInterface $searchDto,
        EntityDtoInterface $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        return $this->container->get(EntityRepository::class)->createQueryBuilder(
            $searchDto,
            $entityDto,
            $fields,
            $filters
        );
    }

    public function renderFilters(AdminContextInterface $context): KeyValueStoreInterface
    {
        $fields = FieldCollection::new($this->configureFields(CrudInterface::PAGE_INDEX));
        $this->container->get(EntityFactory::class)->processFields($context->getEntity(), $fields);
        $filters = $this->container->get(FilterFactory::class)->create(
            $context->getCrud()->getFiltersConfig(),
            $context->getEntity()->getFields(),
            $context->getEntity()
        );

        /** @var FormInterface|FiltersFormType $filtersForm */
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

    public function createEditForm(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormInterface {
        return $this->createEditFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createEditFormBuilder(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormBuilderInterface {
        return $this->container->get(FormFactory::class)->createEditFormBuilder($entityDto, $formOptions, $context);
    }

    public function createNewForm(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormInterface {
        return $this->createNewFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createNewFormBuilder(
        EntityDtoInterface $entityDto,
        KeyValueStoreInterface $formOptions,
        AdminContextInterface $context
    ): FormBuilderInterface {
        return $this->container->get(FormFactory::class)->createNewFormBuilder($entityDto, $formOptions, $context);
    }

    /**
     * Used to add/modify/remove parameters before passing them to the Twig template.
     */
    public function configureResponseParameters(KeyValueStoreInterface $responseParameters): KeyValueStoreInterface
    {
        return $responseParameters;
    }

    protected function getContext(): ?AdminContextInterface
    {
        return $this->container->get(AdminContextProvider::class)->getContext();
    }

    protected function ajaxEdit(
        EntityDtoInterface $entityDto,
        ?string $propertyName,
        bool $newValue
    ): AfterCrudActionEvent {
        $this->container->get(EntityUpdater::class)->updateProperty($entityDto, $propertyName, $newValue);

        $event = new BeforeEntityUpdatedEvent($entityDto->getInstance());
        $this->container->get('event_dispatcher')->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        $this->updateEntity(
            $this->container->get('doctrine')->getManagerForClass($entityDto->getFqcn()),
            $entityInstance
        );

        $this->container->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

        $entityDto->setInstance($entityInstance);

        $parameters = KeyValueStore::new([
            'action' => ActionInterface::EDIT,
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

            if ($state->hasCurrentFiles() && ($state->isDelete() || (!$state->isAddAllowed(
                        ) && $state->hasUploadedFiles()))) {
                foreach ($state->getCurrentFiles() as $file) {
                    $uploadDelete($file);
                }
                $state->setCurrentFiles([]);
            }

            $filePaths = (array)$child->getData();
            $uploadDir = $config->getOption('upload_dir');
            $uploadNew = $config->getOption('upload_new');

            foreach ($state->getUploadedFiles() as $index => $file) {
                $fileName = u($filePaths[$index])->replace($uploadDir, '')->toString();
                $uploadNew($file, $uploadDir, $fileName);
            }
        }
    }

    protected function getRedirectResponseAfterSave(AdminContextInterface $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];

        if (ActionInterface::SAVE_AND_CONTINUE === $submitButtonName) {
            $url = $this->container->get(AdminUrlGenerator::class)
                ->setAction(ActionInterface::EDIT)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl();

            return $this->redirect($url);
        }

        if (ActionInterface::SAVE_AND_RETURN === $submitButtonName) {
            $url = $context->getReferrer()
                ?? $this->container->get(AdminUrlGenerator::class)->setAction(ActionInterface::INDEX)->generateUrl();

            return $this->redirect($url);
        }

        if (ActionInterface::SAVE_AND_ADD_ANOTHER === $submitButtonName) {
            $url = $this->container->get(AdminUrlGenerator::class)->setAction(ActionInterface::NEW)->generateUrl();

            return $this->redirect($url);
        }

        return $this->redirectToRoute($context->getDashboardRouteName());
    }

    protected function getFieldAssets(FieldCollection $fieldDtos): AssetsDtoInterface
    {
        $fieldAssetsDto = new AssetsDto();
        $currentPageName = $this->getContext()?->getCrud()?->getCurrentPage();
        foreach ($fieldDtos as $fieldDto) {
            $fieldAssetsDto = $fieldAssetsDto->mergeWith($fieldDto->getAssets()->loadedOn($currentPageName));
        }

        return $fieldAssetsDto;
    }
}
