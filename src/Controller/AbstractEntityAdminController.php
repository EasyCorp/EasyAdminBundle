<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityAdminConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityAdminActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityAdminActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractEntityAdminController extends AbstractController implements EntityAdminControllerInterface
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

    abstract public function getEntityAdminConfig(): EntityAdminConfig;

    abstract public function getFields(string $action): iterable;

    public function getEntityClass(): string
    {
        return $this->getProcessedConfig()->get('entityFqcn');
    }

    public function getNameInSingular(): string
    {
        return $this->getProcessedConfig()->get('labelInSingular');
    }

    public function getNameInPlural(): string
    {
        return $this->getProcessedConfig()->get('labelInPlural');
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'ea.authorization_checker' => '?'.AuthorizationChecker::class,
        ]);
    }

    public function index(): Response
    {
        $event = new BeforeEntityAdminActionEvent($this->getContext());
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $fields = $this->getFields('index');
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->entity['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $parameters = [
            'paginator' => $paginator,
            'fields' => $fields,
            'batch_form' => $this->createBatchForm($this->entity['name'])->createView(),
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ];

        $event = new AfterEntityAdminActionEvent($this->getContext(), $parameters);
        $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render($this->entity['templates']['list'], $event->getTemplateParameters());
    }

    protected function getProcessedConfig(): EntityAdminConfig
    {
        if (null !== $this->processedConfig) {
            return $this->getProcessedConfig();
        }

        return $this->processedConfig = $this->getEntityAdminConfig();
    }

    protected function getContext(): ?ApplicationContext
    {
        return $this->applicationContextProvider->getContext();
    }
}
