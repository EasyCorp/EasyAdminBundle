<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Builder\FieldBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\IndexPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contacts\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminBatchFormType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class AbstractCrudController extends AbstractController implements CrudControllerInterface
{
    abstract public function configureCrud(): CrudConfig;

    public function configureAssets(): AssetConfig
    {
        return AssetConfig::new();
    }

    /**
     * @inheritDoc
     */
    abstract public function configureFields(string $page): iterable;

    public function configureIndexPage(): IndexPageConfig
    {
        return IndexPageConfig::new();
    }

    public function configureDetailPage(): DetailPageConfig
    {
        return DetailPageConfig::new()
            ->addAction(Action::new('index', 'action.list', null)
                ->linkToMethod('index')
                ->setCssClass('btn btn-link pr-0')
                ->setTranslationDomain('EasyAdminBundle'))

            ->addAction(Action::new('delete', 'action.delete', 'trash-o')
                ->linkToMethod('delete')
                ->setCssClass('btn text-danger')
                ->setTranslationDomain('EasyAdminBundle'))

            ->addAction(Action::new('edit', 'action.edit', null)
                ->linkToMethod('form')
                ->setCssClass('btn btn-primary')
                ->setTranslationDomain('EasyAdminBundle'));
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'ea.authorization_checker' => '?'.AuthorizationChecker::class,
            'event_dispatcher' => '?'.EventDispatcherInterface::class,
            'ea.context_provider' => '?'.ApplicationContextProvider::class,
            'ea.field_builder' => '?'.FieldBuilder::class,
            'ea.entity_paginator' => '?'.EntityPaginator::class,
            'ea.entity_repository' => '?'.EntityRepositoryInterface::class,
        ]);
    }

    public function index(): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $fields = $this->get('ea.field_builder')
            ->setItems(iterator_to_array($this->configureFields('index')))
            ->build();

        $searchFields = $this->getContext()->getPage()->getSearchFields();
        $searchDto = new SearchDto($this->getContext()->getRequest(), $this->getContext()->getPage()->getDefaultSort(), $fields, $searchFields, $this->getContext()->getPage()->getFilters());
        $queryBuilder = $this->createIndexQueryBuilder($searchDto, $this->getContext()->getEntity());
        $pageNumber = $this->getContext()->getRequest()->query->get('page', 1);
        $maxPerPage = $this->getContext()->getPage()->getMaxResults();
        $paginator = $this->get('ea.entity_paginator')->paginate($queryBuilder, $pageNumber, $maxPerPage);

        $parameters = [
            'paginator' => $paginator,
            'fields' => $fields,
            'batch_form' => $this->createBatchForm($this->getContext()->getEntity()->getFqcn())->createView(),
            'delete_form_template' => $this->createDeleteForm('__id__')->createView(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render(
            $this->getContext()->getTemplate('index'),
            $this->getTemplateParameters('index', $event->getTemplateParameters())
        );
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto): QueryBuilder
    {
        return $this->get('ea.entity_repository')->createQueryBuilder($searchDto, $entityDto);
    }

    public function showFilters(): Response
    {
        $filtersForm = $this->get('form.factory')->createNamed('filters', FiltersFormType::class, null, [
            'method' => 'GET',
            'action' => $this->getContext()->getRequest()->query->get('referrer'),
        ]);
        $filtersForm->handleRequest($this->getContext()->getRequest());

        $templateParameters = [
            'filters_form' => $filtersForm->createView(),
        ];

        return $this->render(
            $this->getContext()->getTemplate('filters'),
            $this->getTemplateParameters('filters', $templateParameters)
        );
    }

    public function detail(Request $request): Response
    {
        $event = new BeforeCrudActionEvent($this->getContext());
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $fields = $this->getFields('detail');
        $entityId = $request->query->get('entityId');
        $deleteForm = $this->createDeleteForm($entityId);

        $parameters = [
            'fields' => $fields,
            'delete_form' => $deleteForm->createView(),
        ];

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $this->render(
            $this->getContext()->getTemplate('detail'),
            $this->getTemplateParameters('detail', $event->getTemplateParameters())
        );
    }

    /**
     * Used to add/modify/remove parameters before passing them to the Twig template.
     * $templateName = 'index', 'detail' or 'form'
     */
    public function getTemplateParameters(string $templateName, array $parameters): array
    {
        return $parameters;
    }

    protected function getContext(): ?ApplicationContext
    {
        return $this->get('ea.context_provider')->getContext();
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

    protected function createBatchForm(string $entityName): FormInterface
    {
        return $this->get('form.factory')->create();

        return $this->get('form.factory')->createNamed('batch_form', EasyAdminBatchFormType::class, null, [
            'action' => $this->generateUrl('easyadmin', ['action' => 'batch', 'entity' => $entityName]),
            'entity' => $entityName,
        ]);
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
