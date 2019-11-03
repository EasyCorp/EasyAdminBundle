<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractCrudController extends AbstractController implements CrudControllerInterface
{
    protected $processedConfig;
    private $applicationContextProvider;
    private $doctrine;
    private $eventDispatcher;

    public function __construct(ApplicationContextProvider $applicationContextProvider, RegistryInterface $doctrine, EventDispatcherInterface $eventDispatcher)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
    }

    abstract public function configureCrud(): CrudConfig;

    abstract public function configureFields(string $action): iterable;

    public function configureDetailPage(DetailPageConfig $config): DetailPageConfig
    {
        return $config;
    }

    public function getCrudConfig(): CrudConfig
    {
        if (null !== $this->processedConfig) {
            return $this->processedConfig;
        }

        return $this->processedConfig = $this->configureCrud();
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'ea.authorization_checker' => '?'.AuthorizationChecker::class,
        ]);
    }

    public function index(): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $fields = $this->configureFields('index');
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->entity['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $parameters = [
            'paginator' => $paginator,
            'fields' => $fields,
            'batch_form' => $this->createBatchForm($this->entity['name'])->createView(),
            'delete_form_template' => $this->createDeleteForm('__id__')->createView(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render($this->entity['templates']['list'], $event->getTemplateParameters());
    }

    public function detail(Request $request): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $entityId = $request->query->get('id');
        $entityFqcn = $this->getCrudConfig()->getEntityClass();
        $entity = $this->doctrine->getRepository($entityFqcn)->find($entityId);
        $entityConfig = new EntityConfig($this->doctrine->getEntityManagerForClass($entityFqcn)->getClassMetadata($entityFqcn), $entityId);
        $fields = $this->configureFields('detail');
        $deleteForm = $this->createDeleteForm($entityId);

        $parameters = [
            'page_config' => $this->configureDetailPage(DetailPageConfig::new()),
            'crud_config' => $this->getCrudConfig(),
            'entity' => $entity,
            'entity_config' => $entityConfig,
            'fields' => $fields,
            'delete_form' => $deleteForm->createView(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render($this->getCrudConfig()->getTemplate('detail'), $event->getTemplateParameters());
    }

    protected function getContext(): ?ApplicationContext
    {
        return $this->applicationContextProvider->getContext();
    }

    /**
     * Creates the form used to delete an entity. It must be a form because
     * the deletion of the entity are always performed with the 'DELETE' HTTP method,
     * which requires a form to work in the current browsers.
     *
     * @param int|string $entityId   When reusing the delete form for multiple entities, a pattern string is passed instead of an integer
     */
    protected function createDeleteForm($entityId): FormInterface
    {
        $formBuilder = $this->get('form.factory')->createNamedBuilder('delete_form')
            ->setAction($this->generateUrl('easyadmin', [
                'action' => 'delete',
                'controller' => static::class,
                'id' => $entityId,
            ]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, [
                'label' => 'delete_modal.action',
                'translation_domain' => 'EasyAdminBundle'
            ])
            // needed to avoid submitting empty delete forms (see issue #1409)
            ->add('_easyadmin_delete_flag', HiddenType::class, ['data' => '1']);

        return $formBuilder->getForm();
    }
}
