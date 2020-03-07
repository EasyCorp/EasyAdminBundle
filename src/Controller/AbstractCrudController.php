<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
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
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminBatchFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Property\Property;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractCrudController extends AbstractController implements CrudControllerInterface
{
    public function configureCrud(CrudConfig $crudConfig): CrudConfig
    {
        return $crudConfig;
    }

    public function configureAssets(AssetConfig $assetConfig): AssetConfig
    {
        return $assetConfig;
    }

    public function configureActions(ActionConfig $actionConfig): ActionConfig
    {
        return $actionConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function configureProperties(string $pageName): iterable
    {
        $defaultProperties = $this->get(EntityFactory::class)->create()->getDefaultProperties($pageName);

        return array_map(static function (string $propertyName) {
            return Property::new($propertyName);
        }, $defaultProperties);
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            ActionFactory::class => '?'.ActionFactory::class,
            ApplicationContextProvider::class => '?'.ApplicationContextProvider::class,
            CrudUrlGenerator::class => '?'.CrudUrlGenerator::class,
            EntityFactory::class => '?'.EntityFactory::class,
            EntityRepository::class => '?'.EntityRepository::class,
            EntityUpdater::class => '?'.EntityUpdater::class,
            FormFactory::class => '?'.FormFactory::class,
            PaginatorFactory::class => '?'.PaginatorFactory::class,
        ]);
    }

    public function index()
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $entityDto = $this->get(EntityFactory::class)->create();
        $queryBuilder = $this->createIndexQueryBuilder($this->getContext()->getSearch(), $entityDto);
        $paginator = $this->get(PaginatorFactory::class)->create($queryBuilder);

        $entityInstances = $paginator->getResults();
        $propertiesConfig = $this->configureProperties(CrudConfig::PAGE_INDEX);
        $propertiesConfig = \is_array($propertiesConfig) ? $propertiesConfig : iterator_to_array($propertiesConfig);
        $actionsConfig = $this->get(ActionFactory::class)->create($this->getContext()->getCrud()->getActions());
        $entities = $this->get(EntityFactory::class)->createAll($entityDto, $entityInstances, $propertiesConfig, $actionsConfig);

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => CrudConfig::PAGE_INDEX,
            'templateName' => 'crud/index',
            'entities' => $entities,
            'paginator' => $paginator,
            'batch_form' => $this->createBatchForm($entityDto->getFqcn()),
        ]));

        $event = new AfterCrudActionEvent($this->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function detail()
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $propertiesConfig = $this->configureProperties(Action::DETAIL);
        $actionsConfig = $this->get(ActionFactory::class)->create($this->getContext()->getCrud()->getActions());
        $entityDto = $this->get(EntityFactory::class)->create($propertiesConfig, $actionsConfig);

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => CrudConfig::PAGE_DETAIL,
            'templateName' => 'crud/detail',
            'entity' => $entityDto,
        ]));

        $event = new AfterCrudActionEvent($this->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function edit()
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $propertiesConfig = $this->configureProperties(CrudConfig::PAGE_EDIT);
        $actionsConfig = $this->get(ActionFactory::class)->create($this->getContext()->getCrud()->getActions());
        $entityDto = $this->get(EntityFactory::class)->create($propertiesConfig, $actionsConfig);
        $entityInstance = $entityDto->getInstance();

        if ($this->getContext()->getRequest()->isXmlHttpRequest()) {
            $propertyName = $this->getContext()->getRequest()->query->get('propertyName');
            $newValue = 'true' === mb_strtolower($this->getContext()->getRequest()->query->get('newValue'));

            $event = $this->ajaxEdit($entityDto, $propertyName, $newValue);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }

        $editForm = $this->createEditForm($entityDto);
        $editForm->handleRequest($this->getContext()->getRequest());
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // TODO:
            // $this->processUploadedFiles($editForm);

            $event = new BeforeEntityUpdatedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->updateEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

            $submitButtonName = $this->getContext()->getRequest()->request->get('ea')['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()
                    ->setAction(Action::EDIT)
                    ->setEntityId($entityDto->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            } elseif (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = $this->getContext()->getRequest()->request->get('referrer')
                    ?? $this->get(CrudUrlGenerator::class)->build()->setAction(Action::INDEX)->generateUrl();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => CrudConfig::PAGE_EDIT,
            'templateName' => 'crud/edit',
            'edit_form' => $editForm,
            'entity' => $entityDto->updateInstance($entityInstance),
        ]));

        $event = new AfterCrudActionEvent($this->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function new()
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $propertiesConfig = $this->configureProperties(CrudConfig::PAGE_NEW);
        $actionsConfig = $this->get(ActionFactory::class)->create($this->getContext()->getCrud()->getActions());
        $entityDto = $this->get(EntityFactory::class)->create($propertiesConfig, $actionsConfig);
        $entityInstance = $this->createEntity($entityDto->getFqcn());
        $entityDto = $entityDto->updateInstance($entityInstance);

        $newForm = $this->createNewForm($entityDto);
        $newForm->handleRequest($this->getContext()->getRequest());
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            // TODO:
            // $this->processUploadedFiles($newForm);

            $event = new BeforeEntityPersistedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $this->persistEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityPersistedEvent($entityInstance));
            $entityDto = $entityDto->updateInstance($entityInstance);

            $submitButtonName = $this->getContext()->getRequest()->request->get('ea')['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()
                    ->setAction(Action::EDIT)
                    ->setEntityId($entityDto->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            } elseif (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = $this->getContext()->getRequest()->request->get('referrer')
                    ?? $this->get(CrudUrlGenerator::class)->build()->setAction(Action::INDEX)->generateUrl();

                return $this->redirect($url);
            } elseif (Action::SAVE_AND_ADD_ANOTHER === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()->setAction(Action::NEW)->generateUrl();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(ResponseParameters::new([
            'pageName' => CrudConfig::PAGE_NEW,
            'templateName' => 'crud/new',
            'entity' => $entityDto,
            'new_form' => $newForm,
        ]));

        $event = new AfterCrudActionEvent($this->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function delete()
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $request = $this->getContext()->getRequest();
        if (!$this->isCsrfTokenValid('ea-delete', $request->request->get('token'))) {
            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
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

        $event = new AfterCrudActionEvent($this->getContext(), $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return null !== $request->query->get('referrer')
            ? $this->redirect($request->query->get('referrer'))
            : $this->redirectToRoute($this->getContext()->getDashboardRouteName());
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder
    {
        return $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto);
    }

    public function showFilters(): Response
    {
        /** @var FiltersFormType $filtersForm */
        $filtersForm = $this->get(FormFactory::class)->createFilterForm();
        $formActionParts = parse_url($filtersForm->getConfig()->getAction());
        $queryString = $formActionParts['query'] ?? [];
        parse_str($queryString, $queryStringAsArray);

        $templateParameters = [
            'filters_form' => $filtersForm,
            'form_action_query_string_as_array' => $queryStringAsArray,
        ];

        return $this->render(
            $this->getContext()->getTemplatePath('crud/filters'),
            $this->getResponseParameters('showFilters', $templateParameters)
        );
    }

    protected function createEntity(string $entityFqcn)
    {
        return new $entityFqcn();
    }

    protected function updateEntity(EntityManagerInterface $entityManager, $entityInstance)
    {
        // there's no need to persist the entity explicitly again because it's already
        // managed by Doctrine. The instance is passed to the method in case the
        // user application needs to make decisions
        $entityManager->flush();
    }

    protected function persistEntity(EntityManagerInterface $entityManager, $entityInstance)
    {
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    protected function deleteEntity(EntityManagerInterface $entityManager, $entityInstance)
    {
        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    protected function createEditForm(EntityDto $entityDto): FormInterface
    {
        return $this->get(FormFactory::class)->createEditForm($entityDto);
    }

    protected function createNewForm(EntityDto $entityDto): FormInterface
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

    protected function getContext(): ?ApplicationContext
    {
        return $this->get(ApplicationContextProvider::class)->getContext();
    }

    protected function createBatchForm(string $entityName): FormInterface
    {
        // TODO: fix this
        return $this->get(FormFactory::class)->createDeleteForm([$entityName]);

        return $this->get('form.factory')->createNamed('batch_form', EasyAdminBatchFormType::class, null, [
            'action' => $this->generateUrl('easyadmin', ['action' => 'batch', 'entity' => $entityName]),
            'entity' => $entityName,
        ]);
    }

    private function ajaxEdit(EntityDto $entityDto, ?string $propertyName, bool $newValue): AfterCrudActionEvent
    {
        if (!$entityDto->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" boolean property cannot be changed because either it doesn\'t exist in the "%s" entity.', $propertyName, $entityDto->getName()));
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
