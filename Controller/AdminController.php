<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Controller;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\NoEntitiesConfiguredException;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller used to render all the default EasyAdmin actions.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class AdminController extends Controller
{
    protected $config;
    protected $entity = array();

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $em;

    /**
     * @Route("/", name="easyadmin")
     * @Route("/", name="admin")
     *
     * The 'admin' route is deprecated since version 1.8.0 and it will be removed in 2.0.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $this->initialize($request);

        if (null === $request->query->get('entity')) {
            return $this->redirect($this->generateUrl('easyadmin', array('action' => 'list', 'entity' => $this->config['default_entity_name'])));
        }

        $action = $request->query->get('action', 'list');
        if (!$this->isActionAllowed($action)) {
            throw new ForbiddenActionException(array('action' => $action, 'entity' => $this->entity['name']));
        }

        return $this->executeDynamicMethod($action.'<EntityName>Action');
    }

    /**
     * It renders the main CSS applied to the backend design. This controller
     * allows to generate dynamic CSS files that use variables without the need
     * to set up a CSS preprocessing toolchain.
     *
     * @Route("/_css/easyadmin.css", name="_easyadmin_render_css")
     *
     * @return Response
     */
    public function renderCssAction()
    {
        $config = $this->container->getParameter('easyadmin.config');

        $cssContent = $this->renderView('@EasyAdmin/css/easyadmin.css.twig', array(
            'brand_color' => $config['design']['brand_color'],
            'color_scheme' => $config['design']['color_scheme'],
        ));

        return Response::create($cssContent, 200, array('Content-Type' => 'text/css'))
            ->setPublic()
            ->setSharedMaxAge(600)
            ;
    }

    /**
     * Utility method which initializes the configuration of the entity on which
     * the user is performing the action.
     *
     * @param Request $request
     */
    protected function initialize(Request $request)
    {
        $this->dispatch(EasyAdminEvents::PRE_INITIALIZE);

        $this->config = $this->container->getParameter('easyadmin.config');

        if (0 === count($this->config['entities'])) {
            throw new NoEntitiesConfiguredException();
        }

        // this condition happens when accessing the backend homepage, which
        // then redirects to the 'list' action of the first configured entity
        if (null === $entityName = $request->query->get('entity')) {
            return;
        }

        if (!array_key_exists($entityName, $this->config['entities'])) {
            throw new UndefinedEntityException(array('entity_name' => $entityName));
        }

        $this->entity = $this->get('easyadmin.configurator')->getEntityConfiguration($entityName);

        if (!$request->query->has('sortField')) {
            $request->query->set('sortField', $this->entity['primary_key_field_name']);
        }

        if (!$request->query->has('sortDirection') || !in_array(strtoupper($request->query->get('sortDirection')), array('ASC', 'DESC'))) {
            $request->query->set('sortDirection', 'DESC');
        }

        $this->em = $this->getDoctrine()->getManagerForClass($this->entity['class']);

        $this->request = $request;

        $this->dispatch(EasyAdminEvents::POST_INITIALIZE);
    }

    protected function dispatch($eventName, array $arguments = array())
    {
        $arguments = array_replace(array(
            'config' => $this->config,
            'em' => $this->em,
            'entity' => $this->entity,
            'request' => $this->request,
        ), $arguments);

        $subject = isset($arguments['paginator']) ? $arguments['paginator'] : $arguments['entity'];
        $event = new GenericEvent($subject, $arguments);

        $this->get('event_dispatcher')->dispatch($eventName, $event);
    }

    /**
     * The method that is executed when the user performs a 'list' action on an entity.
     *
     * @return Response
     */
    protected function listAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_LIST);

        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        $this->dispatch(EasyAdminEvents::POST_LIST, array('paginator' => $paginator));

        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }

    /**
     * The method that is executed when the user performs a 'edit' action on an entity.
     *
     * @return RedirectResponse|Response
     */
    protected function editAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_EDIT);

        $id = $this->request->query->get('id');
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];

        if ($this->request->isXmlHttpRequest() && $property = $this->request->query->get('property')) {
            $newValue = 'true' === strtolower($this->request->query->get('newValue'));
            $fieldsMetadata = $this->entity['list']['fields'];

            if (!isset($fieldsMetadata[$property]) || 'toggle' != $fieldsMetadata[$property]['dataType']) {
                throw new \Exception(sprintf('The type of the "%s" property is not "toggle".', $property));
            }

            $this->updateEntityProperty($entity, $property, $newValue);

            return new Response((string) $newValue);
        }

        $fields = $this->entity['edit']['fields'];

        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', array($entity, $fields));
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isValid()) {
            $this->dispatch(EasyAdminEvents::PRE_UPDATE, array('entity' => $entity));

            $this->executeDynamicMethod('preUpdate<EntityName>Entity', array($entity));
            $this->em->flush();

            $this->dispatch(EasyAdminEvents::POST_UPDATE, array('entity' => $entity));

            $refererUrl = $this->request->query->get('referer', '');

            return !empty($refererUrl)
                ? $this->redirect(urldecode($refererUrl))
                : $this->redirect($this->generateUrl('easyadmin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        $this->dispatch(EasyAdminEvents::POST_EDIT);

        return $this->render($this->entity['templates']['edit'], array(
            'form' => $editForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * The method that is executed when the user performs a 'show' action on an entity.
     *
     * @return Response
     */
    protected function showAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_SHOW);

        $id = $this->request->query->get('id');
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];

        $fields = $this->entity['show']['fields'];
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $this->dispatch(EasyAdminEvents::POST_SHOW, array(
            'deleteForm' => $deleteForm,
            'fields' => $fields,
            'entity' => $entity,
        ));

        return $this->render($this->entity['templates']['show'], array(
            'entity' => $entity,
            'fields' => $fields,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * The method that is executed when the user performs a 'new' action on an entity.
     *
     * @return RedirectResponse|Response
     */
    protected function newAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_NEW);

        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');

        $easyadmin = $this->request->attributes->get('easyadmin');
        $easyadmin['item'] = $entity;
        $this->request->attributes->set('easyadmin', $easyadmin);

        $fields = $this->entity['new']['fields'];

        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', array($entity, $fields));

        $newForm->handleRequest($this->request);
        if ($newForm->isValid()) {
            $this->dispatch(EasyAdminEvents::PRE_PERSIST, array('entity' => $entity));

            $this->executeDynamicMethod('prePersist<EntityName>Entity', array($entity));

            $this->em->persist($entity);
            $this->em->flush();

            $this->dispatch(EasyAdminEvents::POST_PERSIST, array('entity' => $entity));

            return $this->redirect($this->generateUrl('easyadmin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        $this->dispatch(EasyAdminEvents::POST_NEW, array(
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ));

        return $this->render($this->entity['templates']['new'], array(
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ));
    }

    /**
     * The method that is executed when the user performs a 'delete' action to
     * remove any entity.
     *
     * @return RedirectResponse
     */
    protected function deleteAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_DELETE);

        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('easyadmin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        $id = $this->request->query->get('id');
        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            $easyadmin = $this->request->attributes->get('easyadmin');
            $entity = $easyadmin['item'];

            $this->dispatch(EasyAdminEvents::PRE_REMOVE, array('entity' => $entity));

            $this->executeDynamicMethod('preRemove<EntityName>Entity', array($entity));

            $this->em->remove($entity);
            $this->em->flush();

            $this->dispatch(EasyAdminEvents::POST_REMOVE, array('entity' => $entity));
        }

        $refererUrl = $this->request->query->get('referer', '');

        $this->dispatch(EasyAdminEvents::POST_DELETE);

        return !empty($refererUrl)
            ? $this->redirect(urldecode($refererUrl))
            : $this->redirect($this->generateUrl('easyadmin', array('action' => 'list', 'entity' => $this->entity['name'])));
    }

    /**
     * The method that is executed when the user performs a query on an entity.
     *
     * @return Response
     */
    protected function searchAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_SEARCH);

        $searchableFields = $this->entity['search']['fields'];
        $paginator = $this->findBy($this->entity['class'], $this->request->query->get('query'), $searchableFields, $this->request->query->get('page', 1), $this->config['list']['max_results']);
        $fields = $this->entity['list']['fields'];

        $this->dispatch(EasyAdminEvents::POST_SEARCH, array(
            'fields' => $fields,
            'paginator' => $paginator,
        ));

        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }

    /**
     * It updates the value of some property of some entity to the new given value.
     *
     * @param mixed  $entity   The instance of the entity to modify
     * @param string $property The name of the property to change
     * @param bool   $value    The new value of the property
     */
    private function updateEntityProperty($entity, $property, $value)
    {
        $entityConfig = $this->entity;

        // the method_exists() check is needed because Symfony 2.3 doesn't have isWritable() method
        if (method_exists($this->get('property_accessor'), 'isWritable') && !$this->get('property_accessor')->isWritable($entity, $property)) {
            throw new \Exception(sprintf('The "%s" property of the "%s" entity is not writable.', $property, $entityConfig['name']));
        }

        $this->dispatch(EasyAdminEvents::PRE_UPDATE, array('entity' => $entity, 'newValue' => $value));

        $this->get('property_accessor')->setValue($entity, $property, $value);

        $this->em->persist($entity);
        $this->em->flush();
        $this->dispatch(EasyAdminEvents::POST_UPDATE, array('entity' => $entity, 'newValue' => $value));

        $this->dispatch(EasyAdminEvents::POST_EDIT);
    }

    /**
     * Creates a new object of the current managed entity.
     * This method is mostly here for override convenience, because it allows
     * the user to use his own method to customize the entity instantiation.
     *
     * @return object
     */
    protected function createNewEntity()
    {
        $entityFullyQualifiedClassName = $this->entity['class'];

        return new $entityFullyQualifiedClassName();
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * created before persisting it.
     *
     * @param object $entity
     */
    protected function prePersistEntity($entity)
    {
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * edited before persisting it.
     *
     * @param object $entity
     */
    protected function preUpdateEntity($entity)
    {
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * deleted before removing it.
     *
     * @param object $entity
     */
    protected function preRemoveEntity($entity)
    {
    }

    /**
     * Performs a database query to get all the records related to the given
     * entity. It supports pagination and field sorting.
     *
     * @param string      $entityClass
     * @param int         $page
     * @param int         $maxPerPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null)
    {
        if (empty($sortDirection) || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
            $sortDirection = 'DESC';
        }

        $queryBuilder = $this->executeDynamicMethod('create<EntityName>ListQueryBuilder', array($entityClass, $sortDirection, $sortField));

        $this->dispatch(EasyAdminEvents::POST_LIST_QUERY_BUILDER, array(
            'query_builder' => $queryBuilder,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ));

        $paginator = new Pagerfanta(new DoctrineORMAdapter($queryBuilder, false, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * Creates Query Builder instance for all the records.
     *
     * @param string      $entityClass
     * @param string      $sortDirection
     * @param string|null $sortField
     *
     * @return QueryBuilder The Query Builder instance
     */
    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null)
    {
        $queryBuilder = $this->em->createQueryBuilder()->select('entity')->from($entityClass, 'entity');

        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.'.$sortField, $sortDirection);
        }

        return $queryBuilder;
    }

    /**
     * Performs a database query based on the search query provided by the user.
     * It supports pagination and field sorting.
     *
     * @param string $entityClass
     * @param string $searchQuery
     * @param array  $searchableFields
     * @param int    $page
     * @param int    $maxPerPage
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findBy($entityClass, $searchQuery, array $searchableFields, $page = 1, $maxPerPage = 15)
    {
        $queryBuilder = $this->executeDynamicMethod('create<EntityName>SearchQueryBuilder', array($entityClass, $searchQuery, $searchableFields));

        $this->dispatch(EasyAdminEvents::POST_SEARCH_QUERY_BUILDER, array(
            'query_builder' => $queryBuilder,
            'search_query' => $searchQuery,
            'searchable_fields' => $searchableFields,
        ));

        $paginator = new Pagerfanta(new DoctrineORMAdapter($queryBuilder, false, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * Creates Query Builder instance for search query.
     *
     * @param string $entityClass
     * @param string $searchQuery
     * @param array  $searchableFields
     *
     * @return QueryBuilder The Query Builder instance
     */
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields)
    {
        $databaseIsPostgreSql = $this->isPostgreSqlUsedByEntity($entityClass);
        $queryBuilder = $this->em->createQueryBuilder()->select('entity')->from($entityClass, 'entity');

        $queryConditions = $queryBuilder->expr()->orX();
        $queryParameters = array();
        foreach ($searchableFields as $name => $metadata) {
            $isNumericField = in_array($metadata['dataType'], array('integer', 'number', 'smallint', 'bigint', 'decimal', 'float'));
            $isTextField = in_array($metadata['dataType'], array('string', 'text', 'guid'));

            if (is_numeric($searchQuery) && $isNumericField) {
                $queryConditions->add(sprintf('entity.%s = :exact_query', $name));
                $queryParameters['exact_query'] = 0 + $searchQuery; // adding '0' turns the string into a numeric value
            } elseif ($isTextField) {
                $queryConditions->add(sprintf('entity.%s LIKE :fuzzy_query', $name));
                $queryParameters['fuzzy_query'] = '%'.$searchQuery.'%';
            } else {
                // PostgreSQL doesn't allow to compare string values with non-string columns (e.g. 'id')
                if ($databaseIsPostgreSql) {
                    continue;
                }

                $queryConditions->add(sprintf('entity.%s IN (:words)', $name));
                $queryParameters['words'] = explode(' ', $searchQuery);
            }
        }

        $queryBuilder->add('where', $queryConditions)->setParameters($queryParameters);

        return $queryBuilder;
    }

    /**
     * Creates the form used to edit an entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     *
     * @return Form
     */
    protected function createEditForm($entity, array $entityProperties)
    {
        return $this->createEntityForm($entity, $entityProperties, 'edit');
    }

    /**
     * Creates the form used to create an entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     *
     * @return Form
     */
    protected function createNewForm($entity, array $entityProperties)
    {
        return $this->createEntityForm($entity, $entityProperties, 'new');
    }

    /**
     * Creates the form builder of the form used to create or edit the given entity.
     *
     * @param object $entity
     * @param string $view   The name of the view where this form is used ('new' or 'edit')
     *
     * @return FormBuilder
     */
    protected function createEntityFormBuilder($entity, $view)
    {
        $formOptions = $this->executeDynamicMethod('get<EntityName>EntityFormOptions', array($entity, $view));

        $formType = $this->useLegacyFormComponent() ? 'easyadmin' : 'JavierEguiluz\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminFormType';

        return $this->get('form.factory')->createNamedBuilder(strtolower($this->entity['name']), $formType, $entity, $formOptions);
    }

    /**
     * Retrieves the list of form options before sending them to the form builder.
     * This allows adding dynamic logic to the default form options.
     *
     * @param object $entity
     * @param string $view
     *
     * @return array
     */
    protected function getEntityFormOptions($entity, $view)
    {
        $formOptions = $this->entity[$view]['form_options'];
        $formOptions['entity'] = $this->entity['name'];
        $formOptions['view'] = $view;

        return $formOptions;
    }

    /**
     * Creates the form object used to create or edit the given entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     * @param string $view
     *
     * @return Form
     *
     * @throws \Exception
     */
    protected function createEntityForm($entity, array $entityProperties, $view)
    {
        if (method_exists($this, $customMethodName = 'create'.$this->entity['name'].'EntityForm')) {
            $form = $this->{$customMethodName}($entity, $entityProperties, $view);
            if (!$form instanceof FormInterface) {
                throw new \Exception(sprintf(
                    'The "%s" method must return a FormInterface, "%s" given.',
                    $customMethodName, is_object($form) ? get_class($form) : gettype($form)
                ));
            }

            return $form;
        }

        $formBuilder = $this->executeDynamicMethod('create<EntityName>EntityFormBuilder', array($entity, $view));

        if (!$formBuilder instanceof FormBuilderInterface) {
            throw new \Exception(sprintf(
                'The "%s" method must return a FormBuilderInterface, "%s" given.',
                'createEntityForm', is_object($formBuilder) ? get_class($formBuilder) : gettype($formBuilder)
            ));
        }

        return $formBuilder->getForm();
    }

    /**
     * Creates the form used to delete an entity. It must be a form because
     * the deletion of the entity are always performed with the 'DELETE' HTTP method,
     * which requires a form to work in the current browsers.
     *
     * @param string $entityName
     * @param int    $entityId
     *
     * @return Form
     */
    protected function createDeleteForm($entityName, $entityId)
    {
        /** @var FormBuilder $formBuilder */
        $formBuilder = $this->get('form.factory')->createNamedBuilder('delete_form')
            ->setAction($this->generateUrl('easyadmin', array('action' => 'delete', 'entity' => $entityName, 'id' => $entityId)))
            ->setMethod('DELETE')
        ;

        $submitButtonType = $this->useLegacyFormComponent() ? 'submit' : 'Symfony\\Component\\Form\\Extension\\Core\\Type\\SubmitType';
        $formBuilder->add('submit', $submitButtonType, array('label' => 'Delete'));

        return $formBuilder->getForm();
    }

    /**
     * Utility method that checks if the given action is allowed for
     * the current entity.
     *
     * @param string $actionName
     *
     * @return bool
     */
    protected function isActionAllowed($actionName)
    {
        return false === in_array($actionName, $this->entity['disabled_actions'], true);
    }

    /**
     * Utility shortcut to render an error when the requested action is not allowed
     * for the given entity.
     *
     * @param string $action
     *
     * @deprecated Use the ForbiddenException instead of this method.
     *
     * @return Response
     */
    protected function renderForbiddenActionError($action)
    {
        return $this->render('@EasyAdmin/error/forbidden_action.html.twig', array('action' => $action), new Response('', 403));
    }

    /**
     * Returns true if the data of the given entity are stored in a database
     * of Type PostgreSQL.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    private function isPostgreSqlUsedByEntity($entityClass)
    {
        $em = $this->get('doctrine')->getManagerForClass($entityClass);

        return $em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    /**
     * Given a method name pattern, it looks for the customized version of that
     * method (based on the entity name) and executes it. If the custom method
     * does not exist, it executes the regular method.
     *
     * For example:
     *   executeDynamicMethod('create<EntityName>Entity') and the entity name is 'User'
     *   if 'createUserEntity()' exists, execute it; otherwise execute 'createEntity()'
     *
     * @param string $methodNamePattern The pattern of the method name (dynamic parts are enclosed with <> angle brackets)
     * @param array  $arguments         The arguments passed to the executed method
     *
     * @return mixed
     */
    private function executeDynamicMethod($methodNamePattern, array $arguments = array())
    {
        $methodName = str_replace('<EntityName>', $this->entity['name'], $methodNamePattern);
        if (!is_callable(array($this, $methodName))) {
            $methodName = str_replace('<EntityName>', '', $methodNamePattern);
        }

        return call_user_func_array(array($this, $methodName), $arguments);
    }

    /**
     * Returns true if the legacy Form component is being used by the application.
     *
     * @return bool
     */
    private function useLegacyFormComponent()
    {
        return false === class_exists('Symfony\\Component\\Form\\Util\\StringUtil');
    }
}
