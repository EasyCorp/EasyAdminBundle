<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
    abstract public function configureProperties(string $action): iterable;

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
        $configuredProperties = iterator_to_array($this->configureProperties('index'));
        $entities = $this->get(EntityFactory::class)->createAll($entityDto, $entityInstances, $configuredProperties);

        $actions = $this->get(ActionFactory::class)->create($this->getContext()->getCrud()->getPage()->getActions());

        $parameters = [
            'actions' => $actions,
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
            $this->getTemplateParameters('index', $event->getTemplateParameters())
        );
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder
    {
        return $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto);
    }

    public function showFilters(): Response
    {
        $templateParameters = [
            'filters_form' => $this->get(FormFactory::class)->createFilterForm(),
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

        $configuredProperties = $this->configureProperties('detail');
        $entityDto = $this->get(EntityFactory::class)->create($configuredProperties);

        $actions = $this->get(ActionFactory::class)->create($this->getContext()->getCrud()->getPage()->getActions());

        $parameters = [
            'actions' => $actions,
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
            $this->getTemplateParameters('detail', $event->getTemplateParameters())
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

        $configuredProperties = $this->configureProperties('edit');
        $entityDto = $this->get(EntityFactory::class)->create($configuredProperties);
        $entityInstance = $entityDto->getInstance();

        /*
        if ($this->request->isXmlHttpRequest() && $property = $this->request->query->get('property')) {
            $newValue = 'true' === mb_strtolower($this->request->query->get('newValue'));
            $fieldsMetadata = $this->entity['list']['fields'];

            if (!isset($fieldsMetadata[$property]) || 'toggle' !== $fieldsMetadata[$property]['dataType']) {
                throw new \RuntimeException(sprintf('The type of the "%s" property is not "toggle".', $property));
            }

            $this->updateEntityProperty($entity, $property, $newValue);

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }
        */

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
            if ('save-and-continue' === $submitButtonName) {
                return $this->redirect($this->get(CrudUrlGenerator::class)->generate([
                    'crudAction' => 'edit',
                    'entityId' => $entityDto->getIdValue(),
                ]));
            } elseif ('save-and-return' === $submitButtonName) {
                return $this->redirect($this->get(CrudUrlGenerator::class)->generate(['crudAction' => 'index']));
            }

            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
        }

        $parameters = [
            'action' => 'edit',
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
            $this->getTemplateParameters('edit', $event->getTemplateParameters())
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

        $configuredProperties = $this->configureProperties('new');
        $entityDto = $this->get(EntityFactory::class)->create($configuredProperties);
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
            if ('save-and-continue' === $submitButtonName) {
                return $this->redirect($this->get(CrudUrlGenerator::class)->generate([
                    'crudAction' => 'edit',
                    'entityId' => $entityDto->getIdValue(),
                ]));
            } elseif ('save-and-return' === $submitButtonName) {
                return $this->redirect($this->get(CrudUrlGenerator::class)->generate(['crudAction' => 'index']));
            } elseif ('save-and-add' === $submitButtonName) {
                return $this->redirect($this->getContext()->getRequest()->getRequestUri());
            }

            return $this->redirectToRoute($this->getContext()->getDashboardRouteName());
        }

        $parameters = [
            'action' => 'new',
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
            $this->getTemplateParameters('new', $event->getTemplateParameters())
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
}
