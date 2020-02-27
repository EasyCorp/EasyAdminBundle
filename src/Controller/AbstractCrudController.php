<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\FormPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\IndexPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
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
    abstract public function configureCrud(CrudConfig $crudConfig): CrudConfig;

    public function configureAssets(AssetConfig $assetConfig): AssetConfig
    {
        return $assetConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function configureProperties(string $action): iterable
    {
        $entityDto = $this->get(EntityFactory::class)->create();
        foreach ($entityDto->getDefaultProperties($action) as $propertyName) {
            yield Property::new($propertyName);
        }
    }

    public function configureIndexPage(IndexPageConfig $indexPageConfig): IndexPageConfig
    {
        return $indexPageConfig;
    }

    public function configureDetailPage(DetailPageConfig $detailPageConfig): DetailPageConfig
    {
        return $detailPageConfig;
    }

    public function configureFormPage(FormPageConfig $formPageConfig): FormPageConfig
    {
        return $formPageConfig;
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

    public function index(): Response
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
        $configuredProperties = iterator_to_array($this->configureProperties(Action::INDEX));
        $configuredActions = $this->getContext()->getCrud()->getPage()->getActions();
        $entities = $this->get(EntityFactory::class)->createAll($entityDto, $entityInstances, $configuredProperties, $configuredActions);

        $parameters = [
            'entities' => $entities,
            'paginator' => $paginator,
            'batch_form' => $this->createBatchForm($entityDto->getFqcn()),
            'delete_form_template' => $this->get(FormFactory::class)->createDeleteForm(['entityId' => '__id__']),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render(
            $this->getContext()->getTemplatePath('crud/index'),
            $this->getTemplateParameters(Action::INDEX, $event->getTemplateParameters())
        );
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
            $this->getTemplateParameters('showFilters', $templateParameters)
        );
    }

    public function detail(): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $configuredProperties = $this->configureProperties(Action::DETAIL);
        $configuredActions = $this->getContext()->getCrud()->getPage()->getActions();
        $entityDto = $this->get(EntityFactory::class)->create($configuredProperties, $configuredActions);

        $parameters = [
            'entity' => $entityDto,
            'delete_form' => $this->get(FormFactory::class)->createDeleteForm(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render(
            $this->getContext()->getTemplatePath('crud/detail'),
            $this->getTemplateParameters(Action::DETAIL, $event->getTemplateParameters())
        );
    }

    public function edit(): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $configuredProperties = $this->configureProperties(Action::EDIT);
        $configuredActions = $this->getContext()->getCrud()->getPage()->getActions();
        $entityDto = $this->get(EntityFactory::class)->create($configuredProperties, $configuredActions);
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
                return $this->redirect($this->get(CrudUrlGenerator::class)->generateCurrentUrl([
                    'crudAction' => Action::EDIT,
                    'entityId' => $entityDto->getIdValue(),
                ]));
            } elseif (Action::SAVE_AND_RETURN === $submitButtonName) {
                return $this->redirect($this->getContext()->getRequest()->request->get('referrer') ?? $this->get(CrudUrlGenerator::class)->generate(['crudAction' => Action::INDEX]));
            }

            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
        }

        $parameters = [
            'action' => Action::EDIT,
            'edit_form' => $editForm,
            'entity' => $entityDto->updateInstance($entityInstance),
            'delete_form' => $this->get(FormFactory::class)->createDeleteForm(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render(
            $this->getContext()->getTemplatePath('crud/edit'),
            $this->getTemplateParameters(Action::EDIT, $event->getTemplateParameters())
        );
    }

    public function new(): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($this->getContext());
        }

        $configuredProperties = $this->configureProperties(Action::NEW);
        $configuredActions = $this->getContext()->getCrud()->getPage()->getActions();
        $entityDto = $this->get(EntityFactory::class)->create($configuredProperties, $configuredActions);
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
                return $this->redirect($this->get(CrudUrlGenerator::class)->generate([
                    'crudAction' => Action::EDIT,
                    'entityId' => $entityDto->getIdValue(),
                ]));
            } elseif (Action::SAVE_AND_RETURN === $submitButtonName) {
                return $this->redirect($this->getContext()->getRequest()->request->get('referrer') ?? $this->get(CrudUrlGenerator::class)->generate(['crudAction' => Action::INDEX]));
            } elseif (Action::SAVE_AND_ADD_ANOTHER === $submitButtonName) {
                return $this->redirect($this->getContext()->getRequest()->getRequestUri());
            }

            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
        }

        $parameters = [
            'action' => Action::NEW,
            'entity' => $entityDto,
            'new_form' => $newForm,
            'delete_form' => $this->get(FormFactory::class)->createDeleteForm(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render(
            $this->getContext()->getTemplatePath('crud/new'),
            $this->getTemplateParameters(Action::NEW, $event->getTemplateParameters())
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
     * $templateName = 'index', 'detail' or 'form'.
     */
    public function getTemplateParameters(string $actionName, array $parameters): array
    {
        foreach ($parameters as $i => $parameter) {
            if ($parameter instanceof FormInterface) {
                $parameters[$i] = $parameter->createView();
            }
        }

        return $parameters;
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

        $parameters = [
            'action' => Action::EDIT,
            'entity' => $entityDto->updateInstance($entityInstance),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);

        return $event;
    }
}
