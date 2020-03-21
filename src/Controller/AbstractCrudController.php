<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
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
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractCrudController extends AbstractController implements CrudControllerInterface
{
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
        $defaultFields = $this->get(EntityFactory::class)->create()->getDefaultProperties($pageName);

        return array_map(static function (string $fieldName) {
            return Field::new($fieldName);
        }, $defaultFields);
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            ActionFactory::class => '?'.ActionFactory::class,
            AdminContextProvider::class => '?'.AdminContextProvider::class,
            CrudUrlGenerator::class => '?'.CrudUrlGenerator::class,
            EntityFactory::class => '?'.EntityFactory::class,
            EntityRepository::class => '?'.EntityRepository::class,
            EntityUpdater::class => '?'.EntityUpdater::class,
            FilterFactory::class => '?'.FilterFactory::class,
            FormFactory::class => '?'.FormFactory::class,
            PaginatorFactory::class => '?'.PaginatorFactory::class,
        ]);
    }

    public function index(CrudRequest $request)
    {
        $event = new BeforeCrudActionEvent($request->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($request->getContext());
        }

        $entityDto = $this->get(EntityFactory::class)->create($request->getEntity());
        $fields = $this->configureFields(Crud::PAGE_INDEX);
        $fields = \is_array($fields) ? $fields : iterator_to_array($fields);
        $queryBuilder = $this->createIndexQueryBuilder($request->getContext()->getSearch(), $entityDto);
        $paginator = $this->get(PaginatorFactory::class)->create($queryBuilder);

        $entityInstances = $paginator->getResults();
        $actions = $this->get(ActionFactory::class)->create($request->getContext()->getCrud()->getActions());
        $entities = $this->get(EntityFactory::class)->createAll($entityDto, $entityInstances, $fields, $actions);

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => Crud::PAGE_INDEX,
            'templateName' => 'crud/index',
            'entities' => $entities,
            'paginator' => $paginator,
            // 'batch_form' => $this->createBatchActionsForm(),
        ]));

        $event = new AfterCrudActionEvent($request->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function detail(CrudRequest $request)
    {
        $event = new BeforeCrudActionEvent($request->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($request->getContext());
        }

        $fields = $this->configureFields(Action::DETAIL);
        $actions = $this->get(ActionFactory::class)->create($request->getContext()->getCrud()->getActions());
        $entityDto = $this->get(EntityFactory::class)->create($request->getEntity(), $fields, $actions);

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => Crud::PAGE_DETAIL,
            'templateName' => 'crud/detail',
            'entity' => $entityDto,
        ]));

        $event = new AfterCrudActionEvent($request->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function edit(CrudRequest $request)
    {
        $event = new BeforeCrudActionEvent($request->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($request->getContext());
        }

        $fields = $this->configureFields(Crud::PAGE_EDIT);
        $actions = $this->get(ActionFactory::class)->create($request->getContext()->getCrud()->getActions());
        $entityDto = $this->get(EntityFactory::class)->create($request->getEntity(), $fields, $actions);
        $entityInstance = $entityDto->getInstance();

        if ($request->getContext()->getRequest()->isXmlHttpRequest()) {
            $fieldName = $request->getContext()->getRequest()->query->get('fieldName');
            $newValue = 'true' === mb_strtolower($request->getContext()->getRequest()->query->get('newValue'));

            $event = $this->ajaxEdit($entityDto, $fieldName, $newValue);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }

        $editForm = $this->createEditForm($entityDto);
        $editForm->handleRequest($request->getContext()->getRequest());
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // TODO:
            // $this->processUploadedFiles($editForm);

            $event = new BeforeEntityUpdatedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->updateEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

            $submitButtonName = $request->getContext()->getRequest()->request->get('ea')['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()
                    ->setAction(Action::EDIT)
                    ->setEntityId($entityDto->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = $request->getReferrer()
                    ?? $this->get(CrudUrlGenerator::class)->build()->setAction(Action::INDEX)->generateUrl();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($request->getContext()->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => Crud::PAGE_EDIT,
            'templateName' => 'crud/edit',
            'edit_form' => $editForm,
            'entity' => $entityDto->updateInstance($entityInstance),
        ]));

        $event = new AfterCrudActionEvent($request->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function new(CrudRequest $request)
    {
        $event = new BeforeCrudActionEvent($request->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($request->getContext());
        }

        $fields = $this->configureFields(Crud::PAGE_NEW);
        $actions = $this->get(ActionFactory::class)->create($request->getContext()->getCrud()->getActions());
        $entityDto = $this->get(EntityFactory::class)->create($request->getEntity(), $fields, $actions);
        $entityInstance = $this->createEntity($entityDto->getFqcn());
        $entityDto = $entityDto->updateInstance($entityInstance);

        $newForm = $this->createNewForm($entityDto);
        $newForm->handleRequest($request->getContext()->getRequest());
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            // TODO:
            // $this->processUploadedFiles($newForm);

            $event = new BeforeEntityPersistedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->persistEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityPersistedEvent($entityInstance));
            $entityDto = $entityDto->updateInstance($entityInstance);

            $submitButtonName = $request->getContext()->getRequest()->request->get('ea')['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()
                    ->setAction(Action::EDIT)
                    ->setEntityId($entityDto->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = $request->getReferrer()
                    ?? $this->get(CrudUrlGenerator::class)->build()->setAction(Action::INDEX)->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_ADD_ANOTHER === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()->setAction(Action::NEW)->generateUrl();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($request->getContext()->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => Crud::PAGE_NEW,
            'templateName' => 'crud/new',
            'entity' => $entityDto,
            'new_form' => $newForm,
        ]));

        $event = new AfterCrudActionEvent($request->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function delete(CrudRequest $request)
    {
        $event = new BeforeCrudActionEvent($request->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($request->getContext());
        }

        $csrfToken = $request->getContext()->getRequest()->request->get('token');
        if (!$this->isCsrfTokenValid('ea-delete', $csrfToken)) {
            return $this->redirectToRoute($request->getContext()->getDashboardRouteName());
        }

        $entityDto = $this->get(EntityFactory::class)->create();
        $entityInstance = $entityDto->getInstance();

        $event = new BeforeEntityDeletedEvent($entityInstance);
        $this->get('event_dispatcher')->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        try {
            $this->deleteEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new EntityRemoveException(['entity_name' => $entityDto->getName(), 'message' => $e->getMessage()]);
        }

        $this->get('event_dispatcher')->dispatch(new AfterEntityDeletedEvent($entityInstance));

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'entity' => $entityDto,
        ]));

        $event = new AfterCrudActionEvent($request->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return null !== $request->getReferrer()
            ? $this->redirect($request->getReferrer())
            : $this->redirectToRoute($request->getContext()->getDashboardRouteName());
    }

    public function autocomplete(CrudRequest $request): JsonResponse
    {
        $entityDto = $this->get(EntityFactory::class)->create($request->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($request->getContext()->getSearch(), $entityDto);
        $paginator = $this->get(PaginatorFactory::class)->create($queryBuilder);

        return JsonResponse::fromJsonString($paginator->getJsonResults());
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder
    {
        return $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto);
    }

    public function renderFilters(CrudRequest $request): ResponseParameters
    {
        $fields = $this->configureFields(Crud::PAGE_INDEX);
        $fields = \is_array($fields) ? $fields : iterator_to_array($fields);

        /** @var FiltersFormType $filtersForm */
        $filtersForm = $this->get(FormFactory::class)->createFiltersForm($request, $fields);
        $formActionParts = parse_url($filtersForm->getConfig()->getAction());
        $queryString = $formActionParts['query'] ?? [];
        parse_str($queryString, $queryStringAsArray);

        $responseParameters = ResponseParameters::new([
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
        // there's no need to persist the entity explicitly again because it's already
        // managed by Doctrine. The instance is passed to the method in case the
        // user application needs to make decisions
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

    public function createEditForm(EntityDto $entityDto): FormInterface
    {
        return $this->get(FormFactory::class)->createEditForm($entityDto);
    }

    public function createNewForm(EntityDto $entityDto): FormInterface
    {
        return $this->get(FormFactory::class)->createNewForm($entityDto);
    }

    /**
     * Used to add/modify/remove parameters before passing them to the Twig template.
     */
    public function configureResponseParameters(ResponseParameters $responseParameters): ResponseParameters
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

        $parameters = ResponseParameters::new([
            'action' => Action::EDIT,
            'entity' => $entityDto->updateInstance($entityInstance),
        ]);

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);

        return $event;
    }
}
