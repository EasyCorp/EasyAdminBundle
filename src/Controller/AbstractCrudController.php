<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contacts\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function configureAssets(): AssetConfig
    {
        return AssetConfig::new();
    }

    public function configureActions(string $page): ActionCollection
    {
        $backToIndex = ActionConfig::new('index', $this->trans('action.list'), null)
            ->setCssClass('btn btn-link pr-0');
        $edit = ActionConfig::new('edit', $this->trans('action.edit'))
            ->setCssClass('btn btn-primary')->setMethodName('form');
        $new = ActionConfig::new('new', $this->trans('action.new'))->setMethodName('form')
            ->setCssClass('btn btn-primary');
        $show = ActionConfig::new('show', $this->trans('action.show'));
        $delete = ActionConfig::new('delete', $this->trans('action.delete'), 'trash-o')
            ->setCssClass('btn text-danger');

        if ('index' === $page) {
            return [];
        }

        if ('detail' === $page) {
            return ActionCollection::new()
                ->addAction($page, $edit)
                ->addAction($page, $delete)
                ->addAction($page, $backToIndex);
        }

        if ('form' === $page) {
            return [];
        }
    }

    private function trans(string $original, array $parameters = []): string
    {
        return $this->get('translator')->trans($original, $parameters, 'EasyAdminBundle');
    }

    /**
     * @inheritDoc
     */
    abstract public function configureFields(string $page): iterable;

    public function configureDetailPage(): DetailPageConfig
    {
        return DetailPageConfig::new()
            ->addAction(ActionConfig::new('edit', $this->trans('action.edit'), null)->setCssClass('btn btn-primary')->setMethodName('form'))
            ->addAction(ActionConfig::new('delete', $this->trans('action.delete'), 'trash-o')->setCssClass('btn text-danger'))
            ->addAction(ActionConfig::new('index', $this->trans('action.list'), null)->setCssClass('btn btn-link pr-0'));
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'ea.authorization_checker' => '?'.AuthorizationChecker::class,
            'translator' => '?'.TranslatorInterface::class,
        ]);
    }

    public function index(): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $fields = $this->getFields('index');
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->entity['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $parameters = [
            'crud_assets' => $this->configureAssets(),
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

        $fields = $this->getFields('detail');
        $entityId = $request->query->get('id');
        $deleteForm = $this->createDeleteForm($entityId);

        $parameters = [
            'crud_assets' => $this->configureAssets(),
            'fields' => $fields,
            'delete_form' => $deleteForm->createView(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render($this->getConfig()->getTemplate('detail'), $event->getTemplateParameters());
    }

    protected function getContext(): ?ApplicationContext
    {
        return $this->applicationContextProvider->getContext();
    }

    protected function getConfig(): Configuration
    {
        return $this->getContext()->getConfig();
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
                'translation_domain' => 'EasyAdminBundle',
            ])
            // needed to avoid submitting empty delete forms (see issue #1409)
            ->add('_easyadmin_delete_flag', HiddenType::class, ['data' => '1']);

        return $formBuilder->getForm();
    }

    /**
     * Filters the page fields to only display the ones which the current user
     * has permission for.
     *
     * @return \EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface[]
     */
    protected function getFields(string $page): iterable
    {
        /** @var \EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface $field */
        foreach ($this->configureFields($page) as $field) {
            if ($this->get('ea.authorization_checker')->isGranted($field->getPermission())) {
                yield $field;
            }
        }
    }
}
