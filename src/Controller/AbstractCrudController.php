<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Model\FileUploadState;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
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

    /**
     * {@inheritdoc}
     */
    public function configureFields(string $pageName): iterable
    {
        return $this->get(FieldProvider::class)->getDefaultFields($pageName);
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            ActionFactory::class => '?'.ActionFactory::class,
            AdminContextProvider::class => '?'.AdminContextProvider::class,
            AdminUrlGenerator::class => '?'.AdminUrlGenerator::class,
            ControllerFactory::class => '?'.ControllerFactory::class,
            CrudUrlGenerator::class => '?'.CrudUrlGenerator::class,
            EntityFactory::class => '?'.EntityFactory::class,
            EntityRepository::class => '?'.EntityRepository::class,
            EntityUpdater::class => '?'.EntityUpdater::class,
            FieldProvider::class => '?'.FieldProvider::class,
            FilterFactory::class => '?'.FilterFactory::class,
            FormFactory::class => '?'.FormFactory::class,
            PaginatorFactory::class => '?'.PaginatorFactory::class,
        ]);
    }

    public function index(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $paginator = $this->get(PaginatorFactory::class)->create($queryBuilder);

        $entities = $this->get(EntityFactory::class)->createCollection($context->getEntity(), $paginator->getResults());
        $this->get(EntityFactory::class)->processFieldsForAll($entities, $fields);
        $actions = $this->get(EntityFactory::class)->processActionsForAll($entities, $context->getCrud()->getActionsConfig());

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
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function detail(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->get(EntityFactory::class)->processFields($context->getEntity(), FieldCollection::new($this->configureFields(Crud::PAGE_DETAIL)));
        $this->get(EntityFactory::class)->processActions($context->getEntity(), $context->getCrud()->getActionsConfig());

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_DETAIL,
            'templateName' => 'crud/detail',
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function edit(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->get(EntityFactory::class)->processFields($context->getEntity(), FieldCollection::new($this->configureFields(Crud::PAGE_EDIT)));
        $this->get(EntityFactory::class)->processActions($context->getEntity(), $context->getCrud()->getActionsConfig());
        $entityInstance = $context->getEntity()->getInstance();

        if ($context->getRequest()->isXmlHttpRequest()) {
            $fieldName = $context->getRequest()->query->get('fieldName');
            $newValue = 'true' === mb_strtolower($context->getRequest()->query->get('newValue'));

            $event = $this->ajaxEdit($context->getEntity(), $fieldName, $newValue);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }

        $editForm = $this->createEditForm($context->getEntity(), $context->getCrud()->getEditFormOptions(), $context);
        $editForm->handleRequest($context->getRequest());
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->processUploadedFiles($editForm);

            $event = new BeforeEntityUpdatedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->updateEntity($this->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

            $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(AdminUrlGenerator::class)
                    ->setAction(Action::EDIT)
                    ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = empty($context->getReferrer())
                    ? $this->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl()
                    : $context->getReferrer();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_EDIT,
            'templateName' => 'crud/edit',
            'edit_form' => $editForm,
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function new(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $context->getEntity()->setInstance($this->createEntity($context->getEntity()->getFqcn()));
        $this->get(EntityFactory::class)->processFields($context->getEntity(), FieldCollection::new($this->configureFields(Crud::PAGE_NEW)));
        $this->get(EntityFactory::class)->processActions($context->getEntity(), $context->getCrud()->getActionsConfig());

        $newForm = $this->createNewForm($context->getEntity(), $context->getCrud()->getNewFormOptions(), $context);
        $newForm->handleRequest($context->getRequest());

        $entityInstance = $newForm->getData();
        $context->getEntity()->setInstance($entityInstance);

        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->processUploadedFiles($newForm);

            $event = new BeforeEntityPersistedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->persistEntity($this->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityPersistedEvent($entityInstance));
            $context->getEntity()->setInstance($entityInstance);

            $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(AdminUrlGenerator::class)
                    ->setAction(Action::EDIT)
                    ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = $context->getReferrer()
                    ?? $this->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_ADD_ANOTHER === $submitButtonName) {
                $url = $this->get(AdminUrlGenerator::class)->setAction(Action::NEW)->generateUrl();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_NEW,
            'templateName' => 'crud/new',
            'entity' => $context->getEntity(),
            'new_form' => $newForm,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function delete(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $csrfToken = $context->getRequest()->request->get('token');
        if (!$this->isCsrfTokenValid('ea-delete', $csrfToken)) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $entityInstance = $context->getEntity()->getInstance();

        $event = new BeforeEntityDeletedEvent($entityInstance);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }
        $entityInstance = $event->getEntityInstance();

        try {
            $this->deleteEntity($this->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new EntityRemoveException(['entity_name' => $context->getEntity()->getName(), 'message' => $e->getMessage()]);
        }

        $this->get('event_dispatcher')->dispatch(new AfterEntityDeletedEvent($entityInstance));

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (null !== $referrer = $context->getReferrer()) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        if (!$this->isCsrfTokenValid('ea-batch-action-'.Action::BATCH_DELETE, $batchActionDto->getCsrfToken())) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());
        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entityInstance = $repository->find($entityId);
            $entityDto = $context->getEntity()->newWithInstance($entityInstance);

            if (!$entityDto->isAccessible()) {
                throw new InsufficientEntityPermissionException($context);
            }

            $event = new BeforeEntityDeletedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            try {
                $this->deleteEntity($entityManager, $entityInstance);
            } catch (ForeignKeyConstraintViolationException $e) {
                throw new EntityRemoveException(['entity_name' => $entityDto, 'message' => $e->getMessage()]);
            }

            $this->get('event_dispatcher')->dispatch(new AfterEntityDeletedEvent($entityInstance));
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'entity' => $context->getEntity(),
            'batchActionDto' => $batchActionDto,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function autocomplete(AdminContext $context): JsonResponse
    {
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), FieldCollection::new([]), FilterCollection::new());

        $autocompleteContext = $context->getRequest()->get(AssociationField::PARAM_AUTOCOMPLETE_CONTEXT);

        /** @var CrudControllerInterface $controller */
        $controller = $this->get(ControllerFactory::class)->getCrudControllerInstance($autocompleteContext[EA::CRUD_CONTROLLER_FQCN], Action::INDEX, $context->getRequest());
        /** @var FieldDto $field */
        $field = FieldCollection::new($controller->configureFields($autocompleteContext['originatingPage']))->getByProperty($autocompleteContext['propertyName']);
        /** @var \Closure|null $queryBuilderCallable */
        $queryBuilderCallable = $field->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE);

        if (null !== $queryBuilderCallable) {
            $queryBuilderCallable($queryBuilder);
        }

        $paginator = $this->get(PaginatorFactory::class)->create($queryBuilder);

        return JsonResponse::fromJsonString($paginator->getResultsAsJson());
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    public function renderFilters(AdminContext $context): KeyValueStore
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $this->get(EntityFactory::class)->processFields($context->getEntity(), $fields);
        $filters = $this->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $context->getEntity()->getFields(), $context->getEntity());

        /** @var FiltersFormType $filtersForm */
        $filtersForm = $this->get(FormFactory::class)->createFiltersForm($filters, $context->getRequest());
        $formActionParts = parse_url($filtersForm->getConfig()->getAction());
        $queryString = $formActionParts[EA::QUERY] ?? [];
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
        return $this->get(FormFactory::class)->createEditFormBuilder($entityDto, $formOptions, $context);
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        return $this->createNewFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->get(FormFactory::class)->createNewFormBuilder($entityDto, $formOptions, $context);
    }

    /**
     * Used to add/modify/remove parameters before passing them to the Twig template.
     */
    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        return $responseParameters;
    }

    private function getContext(): ?AdminContext
    {
        return $this->get(AdminContextProvider::class)->getContext();
    }

    private function ajaxEdit(EntityDto $entityDto, ?string $propertyName, bool $newValue): AfterCrudActionEvent
    {
        if (!$entityDto->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" boolean field cannot be changed because it doesn\'t exist in the "%s" entity.', $propertyName, $entityDto->getName()));
        }

        $this->get(EntityUpdater::class)->updateProperty($entityDto, $propertyName, $newValue);

        $event = new BeforeEntityUpdatedEvent($entityDto->getInstance());
        $this->get('event_dispatcher')->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        $this->updateEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

        $this->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

        $entityDto->setInstance($entityInstance);

        $parameters = KeyValueStore::new([
            'action' => Action::EDIT,
            'entity' => $entityDto,
        ]);

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);

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
}
