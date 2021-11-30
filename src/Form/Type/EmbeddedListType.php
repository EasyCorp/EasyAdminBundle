<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\String\ByteString;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EmbeddedListType extends AbstractType
{
    /** @var AdminContextFactory */
    private $adminContextFactory;

    /** @var AdminContextProvider */
    private $adminContextProvider;

    /** @var AdminUrlGenerator */
    private $adminUrlGenerator;

    /** @var ControllerFactory */
    private $controllerFactory;

    /** @var CrudControllerRegistry */
    private $crudControllerRegistry;

    /** @var DashboardControllerInterface */
    private $dashboardController;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var RequestStack */
    private $requestStack;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Environment */
    private $twig;

    public function __construct(AdminContextFactory $adminContextFactory, AdminContextProvider $adminContextProvider, AdminUrlGenerator $adminUrlGenerator, CrudControllerRegistry $crudControllerRegistry, ControllerFactory $controllerFactory, DashboardControllerInterface $dashboardController, Environment $twig, ManagerRegistry $doctrine, PropertyAccessorInterface $propertyAccessor, RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->adminContextFactory = $adminContextFactory;
        $this->adminContextProvider = $adminContextProvider;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->controllerFactory = $controllerFactory;
        $this->crudControllerRegistry = $crudControllerRegistry;
        $this->dashboardController = $dashboardController;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['content'] = null;
        $entityFqcn = $options['class'];
        if (!$crudControllerFqcn = $this->crudControllerRegistry->findCrudFqcnByEntityFqcn($entityFqcn)) {
            throw new \RuntimeException("Unable to find controller FQCN for entity $entityFqcn");
        }
        if (!$previousRequest = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('Not in request context');
        }
        $request = clone $previousRequest;
        $request->query->set(EA::ENTITY_ID, null);
        if (!$crudController = $this->controllerFactory->getCrudControllerInstance($crudControllerFqcn, Action::INDEX, $request)) {
            throw new \RuntimeException("Unable to find controller for entity $entityFqcn");
        }
        if (!$crudController instanceof AbstractCrudController) {
            throw new \RuntimeException(sprintf('Controller must extend %s', AbstractCrudController::class));
        }
        if (!$context = $this->adminContextFactory->create($request, $this->dashboardController, $crudController)) {
            throw new \RuntimeException('Unable to get admin context from provider');
        }
        $crudController->setIndexQueryFilter($this->getIndexQueryFilter($form, $entityFqcn));
        $responseParameters = $crudController->index($context);
        if (!$responseParameters instanceof KeyValueStore) {
            return;
        }
        if (!$responseParameters->has('templateName') && !$responseParameters->has('templatePath')) {
            throw new \RuntimeException('The KeyValueStore object returned by CrudController actions must include either a "templateName" or a "templatePath" parameter to define the template used to render the action result.');
        }

        $templateParameters = $responseParameters->all();
        $templatePath = $templateParameters['templatePath'] ?? $context->getTemplatePath($templateParameters['templateName']);
        foreach ($templateParameters as $paramName => $paramValue) {
            if ($paramValue instanceof FormInterface) {
                $templateParameters[$paramName] = $paramValue->createView();
            }
        }
        $templateParameters['has_batch_actions'] = false;
        $templateParameters['filters'] = [];
        if (($previousContext = $this->adminContextProvider->getContext()) && $crud = $previousContext->getCrud()) {
            $crud->setSearchFields(null);
        }
        /** @var EntityCollection<EntityDto> $entities */
        $entities = $responseParameters->get('entities');
        foreach ($entities as $entity) {
            $url = $this->adminUrlGenerator->setController($crudControllerFqcn)->setAction(Crud::PAGE_EDIT)->setEntityId($entity->getInstance()->getId())->generateUrl();
            $action = Action::new('edit');
            $action->linkToUrl($url);
            $action->displayAsLink();
            $action->setTemplatePath('@EasyAdmin/crud/action.html.twig');
            $action->setLabel($this->translator->trans('action.edit', [], 'EasyAdminBundle'));
            $actionDto = $action->getAsDto();
            $actionDto->setLinkUrl($url);
            $entity->setActions(ActionCollection::new([
                'edit' => $actionDto,
            ]));
        }

        $view->vars['content'] = 0 === $entities->count()
            ? null
            : $this->twig->load('@EasyAdmin/crud/embedded_list.html.twig')->renderBlock('main', $templateParameters);
    }

    private function getIndexQueryFilter(FormInterface $form, string $entityFqcn)
    {
        $em = $this->doctrine->getManager();
        $metadata = $em->getClassMetadata($entityFqcn);
        $associations = $metadata->getAssociationNames();
        $identifiers = $metadata->getIdentifierFieldNames();

        return function (QueryBuilder $qb) use ($form, $associations, $identifiers): QueryBuilder {
            $alias = current($qb->getRootAliases());
            $parent = $form->getParent();
            if ($parent && ($data = $parent->getData()) && ($name = (new ByteString($parent->getName()))->camel()->toString()) && \in_array($name, $associations)) {
                do {
                    $counter = 0;
                    $joinAlias = "f{$counter}";
                    ++$counter;
                } while (\in_array($joinAlias, $qb->getAllAliases(), true));
                $qb = $qb
                    ->leftJoin("{$alias}.{$name}", $joinAlias)
                    ->andWhere("{$joinAlias}.id = :{$name}Id")
                    ->setParameter("{$name}Id", $data->getId())
                ;
            } else {
                /** @var Collection $data */
                $data = $form->getData() ?? [];
                $data = \is_array($data) ? new ArrayCollection($data) : $data;
                $ids = [];
                foreach ($identifiers as $identifier) {
                    $ids = $data->map(function ($entity) use ($identifier) {
                        return $this->propertyAccessor->getValue($entity, $identifier);
                    })->toArray();
                    $qb = $qb
                        ->andWhere("{$alias}.{$identifier} IN (:{$identifier}s)")
                        ->setParameter("{$identifier}s", array_filter($ids))
                    ;
                }
            }

            return $qb;
        };
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('class', null);
        $resolver->setAllowedTypes('class', 'string');
        $resolver->setRequired('class');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'embedded_list';
    }
}
